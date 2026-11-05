<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PerfilController extends Controller
{
    /** Perfil de la cuenta. Pendiente. */
    public function __invoke(): View
    {
        return view('perfil.show');
    }
}
