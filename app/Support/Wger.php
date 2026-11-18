<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Cliente de la API pública de wger (https://wger.de/api/v2/exerciseinfo/),
 * para la sección "Explorar en wger" de la biblioteca. Es la misma API y la
 * misma lógica que WgerApi + EjercicioWger de la versión Flutter.
 *
 * El catálogo principal de la app sigue siendo RepDB (tabla `ejercicios`):
 * las rutinas y la IA usan ese. wger es un catálogo extra para explorar.
 *
 * wger trae el texto en varios idiomas: se usa el español y, si falta, el
 * inglés. Categorías, músculos y equipamiento vienen en inglés y se traducen
 * con las tablas de abajo (si wger agrega uno nuevo, se muestra tal cual).
 */
class Wger
{
    private const IDIOMA_ESPANOL = 4;

    private const IDIOMA_INGLES = 2;

    private const CATEGORIAS = [
        'Abs' => 'Abdominales',
        'Arms' => 'Brazos',
        'Back' => 'Espalda',
        'Calves' => 'Pantorrillas',
        'Cardio' => 'Cardio',
        'Chest' => 'Pecho',
        'Legs' => 'Piernas',
        'Shoulders' => 'Hombros',
    ];

    private const MUSCULOS = [
        'Shoulders' => 'Hombros',
        'Biceps' => 'Bíceps',
        'Hamstrings' => 'Isquiotibiales',
        'Brachialis' => 'Braquial',
        'Calves' => 'Pantorrillas',
        'Glutes' => 'Glúteos',
        'Lats' => 'Dorsales',
        'Obliquus externus abdominis' => 'Oblicuos',
        'Chest' => 'Pecho',
        'Quads' => 'Cuádriceps',
        'Abs' => 'Abdominales',
        'Serratus anterior' => 'Serrato anterior',
        'Soleus' => 'Sóleo',
        'Trapezius' => 'Trapecio',
        'Triceps' => 'Tríceps',
    ];

    private const EQUIPOS = [
        'Barbell' => 'Barra',
        'Bench' => 'Banco',
        'Cable machine' => 'Polea',
        'Dumbbell' => 'Mancuernas',
        'Gym mat' => 'Colchoneta',
        'Incline bench' => 'Banco inclinado',
        'Kettlebell' => 'Kettlebell',
        'Pull-up bar' => 'Barra de dominadas',
        'Resistance band' => 'Banda elástica',
        'SZ-Bar' => 'Barra Z',
        'Swiss Ball' => 'Pelota suiza',
        'none (bodyweight exercise)' => 'Peso corporal',
    ];

    /**
     * Los ejercicios de wger ya traducidos, los que tienen imagen primero.
     *
     * Cache::remember guarda el resultado por unas horas: la primera visita
     * llama a wger y las siguientes leen la caché. Si wger falla, la
     * excepción sale de la función y NO se guarda nada, así la próxima visita
     * vuelve a intentar (no queda "pegado" un error en la caché).
     *
     * @return Collection<int, array{id: int, nombre: string, descripcion: string, categoria: string, musculos: list<string>, equipamiento: list<string>, imagen: ?string, en_espanol: bool}>
     *
     * @throws ConnectionException si wger no responde a tiempo
     * @throws RequestException si responde con un error (4xx o 5xx)
     */
    public function ejercicios(): Collection
    {
        $config = config('gogym.wger');

        $ejercicios = Cache::remember('wger.ejercicios', now()->addHours($config['cache_horas']), function () use ($config) {
            $respuesta = Http::timeout($config['timeout_segundos'])
                ->acceptJson()
                ->get($config['url'].'/exerciseinfo/', ['limit' => $config['limite'], 'offset' => 0])
                // throw(): si wger responde 4xx o 5xx, lanza RequestException
                // en vez de seguir con un cuerpo de error.
                ->throw();

            // array_filter saca los null (ejercicios sin nombre en español ni inglés).
            return array_values(array_filter(array_map(
                fn (array $ejercicio) => $this->traducir($ejercicio),
                $respuesta->json('results', []),
            )));
        });

        // sortBy es estable: los de imagen quedan primero sin desordenar el resto.
        return collect($ejercicios)->sortBy(fn (array $e) => $e['imagen'] === null)->values();
    }

    /**
     * Pasa un ejercicio de la API al formato de la vista. null si no tiene
     * nombre en español ni en inglés.
     */
    private function traducir(array $json): ?array
    {
        $traducciones = collect($json['translations'] ?? []);
        $espanol = $traducciones->firstWhere('language', self::IDIOMA_ESPANOL);
        $traduccion = $espanol ?? $traducciones->firstWhere('language', self::IDIOMA_INGLES);
        $nombre = trim($traduccion['name'] ?? '');

        if ($traduccion === null || $nombre === '') {
            return null;
        }

        // La imagen marcada como principal o, si ninguna lo está, la primera.
        $imagenes = collect($json['images'] ?? []);
        $imagen = $imagenes->firstWhere('is_main', true) ?? $imagenes->first();

        return [
            'id' => (int) $json['id'],
            'nombre' => $nombre,
            'descripcion' => $this->textoPlano($traduccion['description'] ?? ''),
            'categoria' => $this->nombreEn(self::CATEGORIAS, $json['category']['name'] ?? ''),
            // Los músculos traen el nombre latino ("Biceps femoris") y uno común
            // en inglés ("Hamstrings", en name_en); se traduce el común.
            'musculos' => collect($json['muscles'] ?? [])
                ->map(fn (array $m) => $this->nombreEn(self::MUSCULOS, ($m['name_en'] ?? '') ?: ($m['name'] ?? '')))
                ->all(),
            'equipamiento' => collect($json['equipment'] ?? [])
                ->map(fn (array $e) => $this->nombreEn(self::EQUIPOS, $e['name'] ?? ''))
                ->all(),
            'imagen' => $imagen['image'] ?? null,
            'en_espanol' => $espanol !== null,
        ];
    }

    /** El nombre traducido según la tabla, o el original si no está. */
    private function nombreEn(array $tabla, string $nombre): string
    {
        return $tabla[$nombre] ?? $nombre;
    }

    /**
     * wger manda la descripción en HTML ("<p>Párate…</p><ol><li>…</li></ol>").
     * Se pasa a texto: cada párrafo o paso en su línea, los pasos con viñeta.
     */
    private function textoPlano(string $html): string
    {
        $texto = preg_replace(
            ['/<li[^>]*>/i', '/<br\s*\/?>/i', '/<\/(p|ol|ul|h\d)>/i'],
            ["\n• ", "\n", "\n"],
            $html,
        );
        // strip_tags saca las etiquetas que quedan; html_entity_decode cambia
        // &amp;, &quot;… por su carácter. &nbsp; queda como espacio duro
        // (\u{00A0}), que trim() no quita: se pasa a un espacio normal.
        $texto = html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = str_replace("\u{00A0}", ' ', $texto);

        return collect(explode("\n", $texto))
            ->map(fn (string $linea) => trim($linea))
            ->filter()
            ->join("\n");
    }
}
