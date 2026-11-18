<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarEntrenamientoRequest;
use App\Models\Ejercicio;
use App\Models\Entrenamiento;
use App\Models\Rutina;
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

        return view('entrenamientos.create', [
            'rutinas' => $rutinas,
            'rutina' => $rutina,
            'catalogo' => Ejercicio::query()->orderBy('nombre_orden')->get(['id', 'nombre', 'grupo']),
            'ejerciciosFormulario' => $this->desdeRutina($rutina, $ejercicioLibre),
            'entrenamiento' => null,
        ]);
    }

    public function store(GuardarEntrenamientoRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $rutina = $this->rutinaDeLaCuenta($request, $datos['rutina_id'] ?? null);
        $this->validarEjerciciosDeRutina($rutina, $datos['ejercicios']);

        DB::transaction(function () use ($request, $datos, $rutina): void {
            $inicio = CarbonImmutable::parse($datos['inicio'], config('app.timezone'));
            $entrenamiento = $request->user()->entrenamientos()->create([
                'rutina_id' => $rutina?->id,
                'nombre' => $datos['nombre'],
                'inicio' => $inicio,
                'fin' => $inicio->addMinutes((int) $datos['duracion']),
            ]);

            $this->guardarSeries($entrenamiento, $datos['ejercicios']);
        });

        return redirect()->route('entrenamientos.index')
            ->with('estado', 'Entrenamiento guardado en tu historial.');
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
