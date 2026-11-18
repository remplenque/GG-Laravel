<?php

namespace App\Support;

use App\Models\Ejercicio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Arma la propuesta de rutina de la vista 04 a partir del formulario 03.
 *
 * Tres pasos, que RutinaController usa en este orden:
 *   1. permitidos(): los ejercicios del catálogo que calzan con el equipo y el
 *      nivel elegidos. Es la lista que se le manda a la IA ("elige solo de
 *      aquí") y la que se usa para revisar su respuesta.
 *   2. generar(): arma la propuesta. POR AHORA con reglas fijas, sin IA, para
 *      no esperar a laravel/ai; en el paso 5 adentro va la llamada al
 *      RutinaAgent y nada más cambia (mismo truco que
 *      InicioController::sugerenciaDelDia()).
 *   3. validar(): revisa que la propuesta tenga la forma acordada con el
 *      equipo antes de mostrarla o guardarla. Con la IA es indispensable: un
 *      ejercicio_id inventado rompería la llave foránea de rutina_ejercicios.
 *
 * La propuesta es un arreglo con esta forma (el contrato con el RutinaAgent):
 *
 *   [
 *     'nombre' => 'Torso / Pierna',
 *     'explicacion' => 'Con 4 días…',
 *     'dias' => [
 *       ['nombre' => 'Torso A', 'ejercicios' => [
 *         ['ejercicio_id' => 'bench-press', 'series' => 4, 'repeticiones' => 8,
 *          'descanso_seg' => 120, 'rpe' => 7, 'nota' => null],
 *       ]],
 *     ],
 *   ]
 *
 * $preferencias es lo que valida GenerarRutinaRequest: objetivo, nivel, dias,
 * minutos, equipamiento (nombres de chips) y molestias.
 */
class PropuestaRutina
{
    /** Cuántos ejercicios por grupo muscular entran a la lista permitida. */
    private const POR_GRUPO = 15;

    /**
     * 1 · Ejercicios del catálogo que calzan con el equipo y el nivel.
     *
     * @return Collection<int, Ejercicio> solo con id, nombre y grupo
     */
    public function permitidos(array $preferencias): Collection
    {
        $opciones = config('gogym.rutina_ia');

        // Los chips elegidos ("Barra", "Máquinas"…) traducidos a los valores
        // de la columna equipo (["Barra", "Barra Z", …, "Máquina*", …]).
        // collect(...)->only(...) se queda con las llaves elegidas del mapa y
        // flatten() junta todas las listas en una sola.
        $equipos = collect($opciones['equipamiento'])
            ->only($preferencias['equipamiento'])
            ->flatten()
            ->unique();

        // Niveles que puede hacer: un principiante solo ve ejercicios de
        // principiante; uno intermedio, también los de principiante; uno
        // avanzado, todos. array_search da la posición del nivel elegido.
        $niveles = array_slice(
            $opciones['niveles'], 0, array_search($preferencias['nivel'], $opciones['niveles'], true) + 1
        );

        // Para bajar de peso también sirven los de cardio.
        $categorias = $preferencias['objetivo'] === 'Bajar de peso' ? ['Fuerza', 'Cardio'] : ['Fuerza'];

        $ejercicios = Ejercicio::query()
            ->whereIn('nivel', $niveles)
            ->whereIn('categoria', $categorias)
            // Filtro por equipo: los O van entre paréntesis (un where() con
            // función) para no mezclarse con los whereIn de arriba:
            // nivel Y categoría Y (equipo = 'Barra' O equipo LIKE 'Máquina%' …).
            ->where(function (Builder $consulta) use ($equipos) {
                foreach ($equipos as $equipo) {
                    if (str_ends_with($equipo, '*')) {
                        $consulta->orWhere('equipo', 'like', rtrim($equipo, '*').'%');
                    } else {
                        $consulta->orWhere('equipo', $equipo);
                    }
                }
            })
            ->orderBy('nombre_orden')
            ->get(['id', 'nombre', 'grupo']);

        // Tope por grupo muscular: así la lista no se va solo en Pierna (200
        // ejercicios en el catálogo) y el prompt no gasta tokens de más.
        return $ejercicios
            ->groupBy('grupo')
            ->flatMap(fn (Collection $grupo) => $grupo->take(self::POR_GRUPO))
            ->values();
    }

    /**
     * 2 · La propuesta. Por ahora con reglas fijas (generador de prueba).
     *
     * $variante cambia qué ejercicios elige, para que "Regenerar" muestre
     * otra opción. Con la IA no hará falta: cada respuesta ya sale distinta.
     *
     * PASO 5 · Conectar el RutinaAgent (cuando esté laravel/ai): solo cambia
     * el cuerpo de este método, el resto de la app queda igual.
     *   1. Armar el texto del prompt con $preferencias (sin nombre ni correo:
     *      mitigación legal de la propuesta) y la lista $permitidos como
     *      "id | nombre | grupo", pidiéndole que elija SOLO de esa lista.
     *   2. $respuesta = RutinaAgent::make()->prompt($texto);
     *      El agente declara en schema() la misma forma de la propuesta que
     *      está arriba de esta clase, así que $respuesta['dias'] ya sirve.
     *   3. Devolver ['nombre' => $respuesta['nombre'], 'explicacion' => …, 'dias' => …].
     * RutinaController ya valida lo que vuelve y, si no calza, muestra el
     * aviso en el formulario. Para reintentar una vez antes de rendirse, el
     * controlador puede llamar dos veces a generar() + validar(). Los errores
     * de la IA (Laravel\Ai\Exceptions\…, p. ej. RateLimitedException) también
     * deben terminar en ese aviso, no en un error 500.
     *
     * @param  Collection<int, Ejercicio>  $permitidos
     */
    public function generar(array $preferencias, Collection $permitidos, int $variante = 0): array
    {
        $porGrupo = $permitidos->groupBy('grupo');
        [$nombre, $dias, $explicacion] = $this->division($preferencias['dias']);

        // Más minutos, más ejercicios por día.
        $cantidad = match ((int) $preferencias['minutos']) {
            45 => 4,
            90 => 7,
            default => 5,
        };
        [$series, $repeticiones, $descanso] = match ($preferencias['objetivo']) {
            'Ganar músculo' => [4, 8, 120],
            'Bajar de peso' => [3, 15, 60],
            default => [3, 10, 90],
        };

        $propuesta = [
            'nombre' => $nombre,
            'explicacion' => $explicacion,
            'dias' => [],
        ];

        foreach ($dias as $indice => [$nombreDia, $grupos]) {
            // Recorre los grupos del día en ronda (Pecho, Espalda, Hombro,
            // Pecho…) y de cada uno toma el siguiente ejercicio que no se
            // haya usado en ese día. El desplazamiento ($indice, $variante)
            // hace que "Torso B" no repita los ejercicios de "Torso A".
            $elegidos = [];
            for ($vuelta = 0; count($elegidos) < $cantidad && $vuelta < $cantidad * 3; $vuelta++) {
                $lista = $porGrupo->get($grupos[$vuelta % count($grupos)], collect())->values();
                if ($lista->isEmpty()) {
                    continue;
                }
                $ejercicio = $lista[(intdiv($vuelta, count($grupos)) + $indice + $variante) % $lista->count()];
                $elegidos[$ejercicio->id] = [
                    'ejercicio_id' => $ejercicio->id,
                    'series' => $series,
                    'repeticiones' => $repeticiones,
                    'descanso_seg' => $descanso,
                    'rpe' => 7,
                    'nota' => null,
                ];
            }

            $propuesta['dias'][] = ['nombre' => $nombreDia, 'ejercicios' => array_values($elegidos)];
        }

        return $propuesta;
    }

    /**
     * 3 · Revisa la propuesta. Devuelve solo los campos validados o lanza
     * ValidationException si algo no calza.
     *
     * @param  Collection<int, Ejercicio>  $permitidos
     *
     * @throws ValidationException
     */
    public function validar(array $propuesta, Collection $permitidos, int $dias): array
    {
        $ids = $permitidos->pluck('id')->all();

        // La notación con puntos entra en arreglos anidados y el * es "cada
        // elemento": 'dias.*.ejercicios.*.series' son las series de CADA
        // ejercicio de CADA día. Rule::in($ids) frena los ejercicios inventados.
        return Validator::make($propuesta, [
            'nombre' => 'required|string|max:60',
            'explicacion' => 'required|string|max:600',
            'dias' => "required|array|size:{$dias}",
            'dias.*.nombre' => 'required|string|max:40',
            'dias.*.ejercicios' => 'required|array|min:3|max:10',
            'dias.*.ejercicios.*.ejercicio_id' => ['required', Rule::in($ids)],
            'dias.*.ejercicios.*.series' => 'required|integer|between:1,6',
            'dias.*.ejercicios.*.repeticiones' => 'required|integer|between:0,30',
            'dias.*.ejercicios.*.descanso_seg' => 'required|integer|between:0,300',
            'dias.*.ejercicios.*.rpe' => 'required|integer|between:5,10',
            'dias.*.ejercicios.*.nota' => 'nullable|string|max:200',
        ])->validate();
    }

    /**
     * Cómo se dividen los días: [nombre del plan, [[nombre del día, grupos]…], por qué].
     *
     * @return array{0: string, 1: list<array{0: string, 1: list<string>}>, 2: string}
     */
    private function division(int $dias): array
    {
        $torso = ['Pecho', 'Espalda', 'Hombro', 'Bíceps', 'Tríceps'];
        $pierna = ['Pierna', 'Core'];
        $completo = ['Pierna', 'Pecho', 'Espalda', 'Hombro', 'Core'];
        $empuje = ['Pecho', 'Hombro', 'Tríceps'];
        $tiron = ['Espalda', 'Bíceps'];

        return match (true) {
            $dias <= 3 => [
                'Cuerpo completo',
                array_map(fn ($letra) => ["Cuerpo completo {$letra}", $completo], array_slice(['A', 'B', 'C'], 0, $dias)),
                "Con {$dias} días, entrenar todo el cuerpo en cada sesión hace que cada músculo se trabaje {$dias} veces por semana.",
            ],
            $dias === 4 => [
                'Torso / Pierna',
                [['Torso A', $torso], ['Pierna A', $pierna], ['Torso B', $torso], ['Pierna B', $pierna]],
                'Con 4 días, dividir torso y pierna te deja entrenar cada músculo dos veces por semana con descanso entre sesiones.',
            ],
            default => [
                'Empuje / Tirón / Pierna',
                array_slice([
                    ['Empuje A', $empuje], ['Tirón A', $tiron], ['Pierna A', $pierna],
                    ['Empuje B', $empuje], ['Tirón B', $tiron], ['Pierna B', $pierna],
                ], 0, $dias),
                "Con {$dias} días, separar empuje, tirón y pierna da sesiones cortas y enfocadas, y cada músculo descansa antes de repetirse.",
            ],
        };
    }
}
