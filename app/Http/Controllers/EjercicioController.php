<?php

namespace App\Http\Controllers;

use App\Models\Ejercicio;
use Illuminate\View\View;

class EjercicioController extends Controller
{
    /** Biblioteca de ejercicios. Pendiente. */
    public function index(): View
    {
        return view('ejercicios.index');
    }

    /** Vista 06 · Detalle de ejercicio. Pendiente. */
    public function show(Ejercicio $ejercicio): View
    {
        return view('ejercicios.show', ['ejercicio' => $ejercicio]);
    }
}
