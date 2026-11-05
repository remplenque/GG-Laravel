<?php

namespace App\Http\Controllers;

use App\Models\Rutina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RutinaController extends Controller
{
    /** Rutinas de la cuenta. La que toca según la rotación va destacada. */
    public function index(Request $request): View
    {
        $user = $request->user();
        $rutinas = $user->rutinas()->with('ejercicios.ejercicio')->get();
        $siguiente = Rutina::siguiente($rutinas, $user->entrenamientos()->get(['id', 'rutina_id']));

        // Lugar de cada rutina en la rotación (solo las que se pueden empezar).
        $orden = $rutinas
            ->filter(fn (Rutina $r) => $r->ejercicios->isNotEmpty())
            ->values()
            ->mapWithKeys(fn (Rutina $r, int $i) => [$r->id => $i + 1]);

        return view('rutinas.index', [
            'rutinas' => $rutinas,
            'siguiente' => $siguiente,
            'orden' => $orden,
        ]);
    }

    /** Detalle de una rutina: grupos musculares y sus ejercicios. */
    public function show(Rutina $rutina): View
    {
        Gate::authorize('view', $rutina);

        return view('rutinas.show', [
            'rutina' => $rutina->load('ejercicios.ejercicio'),
        ]);
    }

    /** Vista 03 · Generar rutina con IA. Pendiente. */
    public function generar(): View
    {
        return view('rutinas.generar');
    }
}
