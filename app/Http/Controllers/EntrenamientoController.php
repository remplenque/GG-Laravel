<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarEntrenamientoRequest;
use App\Models\Ejercicio;
use App\Models\Entrenamiento;
use App\Models\EntrenamientoSerie;
use App\Models\Rutina;
use App\Support\Formato;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EntrenamientoController extends Controller
{
    /** Historial paginado de la cuenta, con acciones de edición y borrado. */
    public function index(Request $request): View
    {
        $entrenamientos = $request->user()->entrenamientos()
            ->with('series.ejercicio')
            ->paginate(10);

        return view('entrenamientos.index', compact('entrenamientos'));
    }

    /** Vista 05 · Registrar entrenamiento. */
    public function create(Request $request): View
    {
        $rutinas = $request->user()->rutinas()->with('ejercicios.ejercicio')->get();
        $rutina = $rutinas->firstWhere('id', $request->integer('rutina'));
        $ejercicioLibre = $rutina === null
            ? Ejercicio::find($request->string('ejercicio')->toString())
            : null;

        $ejerciciosFormulario = $this->desdeRutina($rutina, $ejercicioLibre);

        return view('entrenamientos.create', [
            'rutinas' => $rutinas,
            'rutina' => $rutina,
            'catalogo' => Ejercicio::query()->orderBy('nombre_orden')->get(['id', 'nombre', 'grupo']),
            'ejerciciosFormulario' => $ejerciciosFormulario,
            'marcasPrevias' => $this->ultimasMarcas($request, array_column($ejerciciosFormulario, 'id')),
            'entrenamiento' => null,
        ]);
    }

    public function store(GuardarEntrenamientoRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $rutina = $this->rutinaDeLaCuenta($request, $datos['rutina_id'] ?? null);
        $this->validarEjerciciosDeRutina($rutina, $datos['ejercicios']);

        // Las mejores cargas ANTES de guardar, para detectar récords nuevos.
        $ids = collect($datos['ejercicios'])->pluck('ejercicio_id')->all();
        $mejoresAntes = $this->mejoresCargas($request, $ids);

        $entrenamiento = DB::transaction(function () use ($request, $datos, $rutina): Entrenamiento {
            $inicio = CarbonImmutable::parse($datos['inicio'], config('app.timezone'));
            $entrenamiento = $request->user()->entrenamientos()->create([
                'rutina_id' => $rutina?->id,
                'nombre' => $datos['nombre'],
                'inicio' => $inicio,
                'fin' => $inicio->addMinutes((int) $datos['duracion']),
            ]);

            $this->guardarSeries($entrenamiento, $datos['ejercicios']);

            return $entrenamiento;
        });

        $entrenamiento->load('series.ejercicio');
        $records = $this->recordsNuevos($entrenamiento, $mejoresAntes);

        $resumen = collect([
            $entrenamiento->series->count().' '.($entrenamiento->series->count() === 1 ? 'serie' : 'series'),
            $entrenamiento->volumen > 0 ? Formato::miles($entrenamiento->volumen).' kg de volumen' : null,
        ])->filter()->join(' · ');

        $mensaje = $records === []
            ? "¡Entrenamiento guardado! {$resumen}."
            : '¡Nuevo récord en '.collect($records)->join(', ', ' y ')."! {$resumen}.";

        return redirect()->route('entrenamientos.index')
            ->with('estado', $mensaje)
            ->with('celebrar', true)
            ->with('destacar', $entrenamiento->id);
    }

    public function edit(Request $request, Entrenamiento $entrenamiento): View
    {
        $this->autorizar($request, $entrenamiento);
        $entrenamiento->load('series.ejercicio', 'rutina');

        return view('entrenamientos.edit', [
            'rutinas' => $request->user()->rutinas()->with('ejercicios.ejercicio')->get(),
            'rutina' => $entrenamiento->rutina,
            'catalogo' => collect(),
            'ejerciciosFormulario' => $entrenamiento->series
                ->groupBy('ejercicio_id')
                ->map(fn ($series) => [
                    'id' => $series->first()->ejercicio_id,
                    'nombre' => $series->first()->ejercicio?->nombre ?? 'Ejercicio',
                    'grupo' => $series->first()->ejercicio?->grupo,
                    'series' => $series->map(fn ($serie) => [
                        'kg' => $serie->kg,
                        'reps' => $serie->reps,
                        'rpe' => $serie->rpe,
                    ])->values()->all(),
                ])->values()->all(),
            'marcasPrevias' => $this->ultimasMarcas(
                $request,
                $entrenamiento->series->pluck('ejercicio_id')->unique()->values()->all(),
                $entrenamiento,
            ),
            'entrenamiento' => $entrenamiento,
        ]);
    }

    public function update(
        GuardarEntrenamientoRequest $request,
        Entrenamiento $entrenamiento,
    ): RedirectResponse {
        $this->autorizar($request, $entrenamiento);
        $datos = $request->validated();
        $rutina = $this->rutinaDeLaCuenta($request, $datos['rutina_id'] ?? null);
        $this->validarEjerciciosDeRutina($rutina, $datos['ejercicios']);

        DB::transaction(function () use ($datos, $entrenamiento, $rutina): void {
            $inicio = CarbonImmutable::parse($datos['inicio'], config('app.timezone'));
            $entrenamiento->update([
                'rutina_id' => $rutina?->id,
                'nombre' => $datos['nombre'],
                'inicio' => $inicio,
                'fin' => $inicio->addMinutes((int) $datos['duracion']),
            ]);
            $entrenamiento->series()->delete();
            $this->guardarSeries($entrenamiento, $datos['ejercicios']);
        });

        return redirect()->route('entrenamientos.index')
            ->with('estado', 'Entrenamiento actualizado.');
    }

    public function destroy(Request $request, Entrenamiento $entrenamiento): RedirectResponse
    {
        $this->autorizar($request, $entrenamiento);
        $entrenamiento->delete();

        return redirect()->route('entrenamientos.index')
            ->with('estado', 'Entrenamiento eliminado del historial.');
    }

    /** @return list<array{id:string,nombre:string,grupo:?string,series:list<array{kg:mixed,reps:mixed,rpe:mixed}>}> */
    private function desdeRutina(?Rutina $rutina, ?Ejercicio $libre): array
    {
        if ($rutina !== null) {
            return $rutina->ejercicios->map(fn ($plan) => [
                'id' => $plan->ejercicio_id,
                'nombre' => $plan->ejercicio?->nombre ?? 'Ejercicio',
                'grupo' => $plan->ejercicio?->grupo,
                'series' => array_fill(0, max(1, min(8, $plan->series)), [
                    'kg' => null,
                    'reps' => $plan->repeticiones ?: null,
                    'rpe' => null,
                ]),
            ])->values()->all();
        }

        return $libre === null ? [] : [[
            'id' => $libre->id,
            'nombre' => $libre->nombre,
            'grupo' => $libre->grupo,
            'series' => array_fill(0, 3, ['kg' => null, 'reps' => null, 'rpe' => null]),
        ]];
    }

    /**
     * La última vez que la cuenta hizo cada ejercicio: la fecha y sus series
     * en orden. El formulario las muestra como sugerencia (placeholder) en
     * cada fila, así se ve al tiro qué peso usar.
     *
     * @param  list<string>  $ids
     * @return array<string, array{fecha: string, series: list<array{kg: ?float, reps: ?int}>}>
     */
    private function ultimasMarcas(Request $request, array $ids, ?Entrenamiento $excluir = null): array
    {
        if ($ids === []) {
            return [];
        }

        $series = EntrenamientoSerie::query()
            ->join('entrenamientos', 'entrenamientos.id', '=', 'entrenamiento_series.entrenamiento_id')
            ->where('entrenamientos.user_id', $request->user()->id)
            ->whereIn('entrenamiento_series.ejercicio_id', $ids)
            ->when($excluir, fn ($consulta) => $consulta->where('entrenamientos.id', '!=', $excluir->id))
            ->orderByDesc('entrenamientos.inicio')
            ->orderBy('entrenamiento_series.numero')
            ->get([
                'entrenamiento_series.ejercicio_id',
                'entrenamiento_series.entrenamiento_id',
                'entrenamiento_series.kg',
                'entrenamiento_series.reps',
                'entrenamientos.inicio',
            ]);

        return $series->groupBy('ejercicio_id')->map(function ($grupo): array {
            $sesion = $grupo->first()->entrenamiento_id;

            return [
                'fecha' => Formato::diaRelativo(CarbonImmutable::parse($grupo->first()->inicio), now()),
                'series' => $grupo->where('entrenamiento_id', $sesion)
                    ->map(fn ($serie) => ['kg' => $serie->kg, 'reps' => $serie->reps])
                    ->values()
                    ->all(),
            ];
        })->all();
    }

    /**
     * La carga más alta de la cuenta en cada ejercicio, como [id => kg].
     *
     * @param  list<string>  $ids
     * @return array<string, float>
     */
    private function mejoresCargas(Request $request, array $ids): array
    {
        return EntrenamientoSerie::query()
            ->whereIn('ejercicio_id', $ids)
            ->whereNotNull('kg')
            ->whereHas('entrenamiento', fn ($consulta) => $consulta->where('user_id', $request->user()->id))
            ->groupBy('ejercicio_id')
            ->selectRaw('ejercicio_id, MAX(kg) as mejor')
            ->get()
            ->mapWithKeys(fn ($fila) => [$fila->ejercicio_id => (float) $fila->mejor])
            ->all();
    }

    /**
     * Nombres de los ejercicios donde esta sesión superó la mejor carga
     * anterior. La primera vez que se hace un ejercicio no cuenta como
     * récord (no hay con qué comparar).
     *
     * @param  array<string, float>  $mejoresAntes
     * @return list<string>
     */
    private function recordsNuevos(Entrenamiento $entrenamiento, array $mejoresAntes): array
    {
        return $entrenamiento->series
            ->whereNotNull('kg')
            ->groupBy('ejercicio_id')
            ->filter(fn ($series, $id) => isset($mejoresAntes[$id]) && $series->max('kg') > $mejoresAntes[$id])
            ->map(fn ($series) => $series->first()->ejercicio?->nombre)
            ->filter()
            ->values()
            ->all();
    }

    private function guardarSeries(Entrenamiento $entrenamiento, array $ejercicios): void
    {
        foreach ($ejercicios as $orden => $ejercicio) {
            $numero = 0;
            foreach ($ejercicio['series'] as $serie) {
                if (! filled($serie['reps'] ?? null)) {
                    continue;
                }

                $entrenamiento->series()->create([
                    'ejercicio_id' => $ejercicio['ejercicio_id'],
                    'orden' => $orden,
                    'numero' => ++$numero,
                    'kg' => filled($serie['kg'] ?? null) ? $serie['kg'] : null,
                    'reps' => $serie['reps'],
                    'rpe' => filled($serie['rpe'] ?? null) ? $serie['rpe'] : null,
                ]);
            }
        }
    }

    private function rutinaDeLaCuenta(Request $request, mixed $id): ?Rutina
    {
        if (! filled($id)) {
            return null;
        }

        return $request->user()->rutinas()->with('ejercicios')->findOrFail($id);
    }

    private function validarEjerciciosDeRutina(?Rutina $rutina, array $ejercicios): void
    {
        if ($rutina === null) {
            return;
        }

        $enviados = collect($ejercicios)->pluck('ejercicio_id')->sort()->values();
        $planificados = $rutina->ejercicios->pluck('ejercicio_id')->sort()->values();
        abort_unless($enviados->all() === $planificados->all(), 422);
    }

    private function autorizar(Request $request, Entrenamiento $entrenamiento): void
    {
        abort_unless($entrenamiento->user_id === $request->user()->id, 403);
    }
}
