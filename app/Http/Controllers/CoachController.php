<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CoachController extends Controller
{
    /** Vista 02 · Coach IA (chat). Pendiente. */
    public function __invoke(): View
    {
        return view('coach.index');
    }
}
