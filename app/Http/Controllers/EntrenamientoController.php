<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class EntrenamientoController extends Controller
{
    /** Historial de entrenamientos. Pendiente. */
    public function index(): View
    {
        return view('entrenamientos.index');
    }

    /** Vista 05 · Registrar entrenamiento. Pendiente. */
    public function create(Request $request): View
    {
        // Si viene de "Empezar" en una rutina, se registra a partir de ella.
        $rutina = $request->user()->rutinas()->find($request->integer('rutina'));

        return view('entrenamientos.create', ['rutina' => $rutina]);
    }
}
