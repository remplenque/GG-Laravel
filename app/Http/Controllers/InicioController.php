<?php

namespace App\Http\Controllers;

use App\Models\Entrenamiento;
use App\Models\Rutina;
use App\Models\User;
use App\Support\Formato;
use App\Support\Historial;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Vista 01 · Inicio (dashboard).
 *
 * El trabajo del controlador es conseguir los datos y elegir la vista: no arma
 * HTML ni escribe SQL. Junta la racha, los totales de la semana, la rutina que
 * toca con su sugerencia del día y las últimas sesiones, todo de la cuenta con
 * sesión, y se lo pasa a resources/views/inicio.blade.php.
 *
 * Tiene un solo método (__invoke), por eso en routes/web.php la ruta se
 * declara con la clase sola: Route::get('/', InicioController::class).
 */
class InicioController extends Controller
{
    public function __invoke(Request $request): View
    {
        // $request->user() es el usuario con sesión (la ruta exige 'auth').
        $user = $request->user();
        // "Hoy" inmutable: cada cálculo con fechas devuelve una copia y no
        // cambia esta variable por accidente.
        $hoy = now()->toImmutable();

        // Todos los entrenamientos de la cuenta, del más reciente al más
        // antiguo (así lo ordena la relación en el modelo User).
        $entrenamientos = $user->entrenamientos()->get();
        // Historial calcula la racha, la semana y qué días se entrenó.
        $historial = new Historial($entrenamientos);

        // Entrenamientos de esta semana (lunes a domingo) y los últimos N
        // (N se configura en config/gogym.php → 'ultimas_sesiones').
        $semana = $historial->deLaSemana($hoy);
        $ultimas = $entrenamientos->take(config('gogym.ultimas_sesiones'));
        // Solo estas sesiones necesitan sus series (para el volumen, las kcal
        // y el grupo muscular). load() las trae en UNA consulta para todas, en
        // vez de una consulta por sesión.
        $semana->merge($ultimas)->unique('id')->load('series.ejercicio');

        // La rutina que toca según la rotación (la que sigue a la última hecha).
        $rutinas = $user->rutinas()->with('ejercicios.ejercicio')->get();
        $siguiente = Rutina::siguiente($rutinas, $entrenamientos);

        // Todo lo que va dentro de este arreglo llega a la vista como variable:
        // la llave 'racha' se lee en Blade como $racha, 'ultimas' como $ultimas…
        return view('inicio', [
            'user' => $user,
            'hoy' => $hoy,
            'racha' => [
                'actual' => $historial->rachaActual($hoy),
                'record' => $historial->rachaRecord(),
                // Los 7 cuadritos de la semana con su estado (entrenado, hoy…).
                'dias' => $historial->semana($hoy),
            ],
            'estaSemana' => [
                'sesiones' => $semana->count(),
                // volumen = suma de kg × reps de todas las series de la semana.
                'volumen' => $semana->sum('volumen'),
                'minutos' => $semana->sum('duracion_minutos'),
                // Calorías estimadas de la semana. Necesitan el peso del perfil:
                // sin peso queda en null y la vista muestra el tiempo en su lugar.
                'kcal' => $user->peso_kg
                    ? $semana->sum(fn (Entrenamiento $e) => $e->kcalEstimadas($user->peso_kg))
                    : null,
            ],
            'siguiente' => $siguiente,
            // Texto de la tarjeta violeta "Coach IA · Sugerencia de hoy".
            'sugerencia' => $siguiente
                ? $this->sugerenciaDelDia($user, $siguiente, $entrenamientos, $historial->entrenoEl($hoy), $hoy)
                : null,
            'ultimas' => $ultimas,
        ]);
    }

    /**
     * Sugerencia del día para la tarjeta del Coach IA.
     *
     * Por ahora se arma con reglas a partir del historial, sin llamar a la IA:
     * qué rutina toca y, si en la última vez subió la carga de algún ejercicio,
     * un consejo para consolidarla. Cuando se integre la IA (semana 3, modelo
     * llama-3.1-8b-instant de Groq) basta con reemplazar el texto que devuelve
     * este método por la respuesta del agente: la vista no cambia, porque solo
     * recibe un string en $sugerencia.
     *
     * @param  Collection<int, Entrenamiento>  $entrenamientos  más recientes primero
     */
    private function sugerenciaDelDia(
        User $user,
        Rutina $rutina,
        Collection $entrenamientos,
        bool $entrenoHoy,
        CarbonInterface $hoy,
    ): string {
        $apertura = $this->aperturaSugerencia($user, $rutina, $entrenoHoy, $hoy);

        // Las dos últimas veces que se hizo ESTA rutina, para compararlas.
        $vecesAnteriores = $entrenamientos->where('rutina_id', $rutina->id)->take(2)->values();

        if ($vecesAnteriores->isEmpty()) {
            return "{$apertura} Es la primera vez que la haces: parte con una carga cómoda y cuida la técnica.";
        }
        if ($vecesAnteriores->count() === 1) {
            return "{$apertura} Repite las cargas de la última vez y busca completar todas las repeticiones.";
        }

        // Trae las series de esas dos sesiones en una sola consulta.
        $vecesAnteriores->load('series');
        $ultima = $this->cargaMaximaPorEjercicio($vecesAnteriores[0]);
        $penultima = $this->cargaMaximaPorEjercicio($vecesAnteriores[1]);

        // Cuánto subió cada ejercicio entre la penúltima y la última vez; se
        // queda solo con los que subieron, el que más subió primero.
        $subidas = $ultima
            ->map(fn ($kg, $id) => $kg - ($penultima[$id] ?? $kg))
            ->filter(fn ($diferencia) => $diferencia > 0)
            ->sortDesc();

        if ($subidas->isEmpty()) {
            return "{$apertura} Repite las cargas de la última vez y busca completar todas las repeticiones.";
        }

        // El ejercicio que más subió, con su nombre y las repeticiones que pide
        // la rutina (si no está en la rutina, se usa un texto genérico).
        $ejercicioId = $subidas->keys()->first();
        $planificado = $rutina->ejercicios->firstWhere('ejercicio_id', $ejercicioId);
        // En minúsculas porque va a mitad de frase: "Tu press de banca subió…".
        $nombre = Str::lower($planificado?->ejercicio?->nombre ?? 'ejercicio principal');
        $repeticiones = $planificado?->repeticiones ?: null;

        $consejo = "Tu {$nombre} subió {$this->kg($subidas->first())} kg desde la vez anterior: mantén {$this->kg($ultima[$ejercicioId])} kg";

        return $repeticiones
            ? "{$apertura} {$consejo} y busca {$repeticiones} repeticiones en todas las series antes de subir."
            : "{$apertura} {$consejo} hasta que te salga cómodo antes de subir.";
    }

    /**
     * Primera frase de la sugerencia: "Hoy te toca …", "Hoy ya entrenaste…" o
     * el próximo día de entrenamiento según los días elegidos en el perfil.
     */
    private function aperturaSugerencia(User $user, Rutina $rutina, bool $entrenoHoy, CarbonInterface $hoy): string
    {
        if ($entrenoHoy) {
            return "Hoy ya entrenaste. Lo próximo es {$rutina->titulo}.";
        }
        if ($user->esDiaEntrenamiento($hoy)) {
            return "Hoy te toca {$rutina->titulo}.";
        }

        $proximo = $user->proximoDiaEntrenamiento($hoy);

        // Sin días de entrenamiento en el perfil no hay "próximo día".
        return $proximo === null
            ? "Tu siguiente rutina es {$rutina->titulo}."
            : 'Hoy descansas. El '.Str::lower(Formato::nombreDia($proximo))." te toca {$rutina->titulo}.";
    }

    /**
     * La carga más alta que se usó en cada ejercicio de una sesión, como
     * [id del ejercicio => kg]. Las series sin kg (peso corporal, por tiempo)
     * no cuentan.
     *
     * @return \Illuminate\Support\Collection<string, float>
     */
    private function cargaMaximaPorEjercicio(Entrenamiento $entrenamiento): \Illuminate\Support\Collection
    {
        return $entrenamiento->series
            ->whereNotNull('kg')
            ->groupBy('ejercicio_id')
            ->map(fn ($series) => (float) $series->max('kg'));
    }

    /** Kilos sin decimales de sobra y con coma: 50.0 → "50", 37.5 → "37,5". */
    private function kg(float $kg): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format($kg, 1, '.', ''), '0'), '.'));
    }
}
