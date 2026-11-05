<?php

namespace App\Http\Controllers;

use App\Models\Rutina;
use App\Models\User;
use App\Support\Formato;
use App\Support\Historial;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard (vista 01): racha, totales de la semana, la rutina que toca y las
 * últimas sesiones. Todo sale de la cuenta con sesión.
 */
class InicioController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $hoy = now()->toImmutable();

        $entrenamientos = $user->entrenamientos()->get();
        $historial = new Historial($entrenamientos);

        $semana = $historial->deLaSemana($hoy);
        $ultimas = $entrenamientos->take(config('gogym.ultimas_sesiones'));
        // Solo estas necesitan las series para el volumen y el grupo.
        $semana->merge($ultimas)->unique('id')->load('series.ejercicio');

        $rutinas = $user->rutinas()->with('ejercicios.ejercicio')->get();
        $siguiente = Rutina::siguiente($rutinas, $entrenamientos);

        return view('inicio', [
            'user' => $user,
            'hoy' => $hoy,
            'racha' => [
                'actual' => $historial->rachaActual($hoy),
                'record' => $historial->rachaRecord(),
                'dias' => $historial->semana($hoy),
            ],
            'estaSemana' => [
                'sesiones' => $semana->count(),
                'volumen' => $semana->sum('volumen'),
                'minutos' => $semana->sum('duracion_minutos'),
            ],
            'siguiente' => $siguiente,
            'etiquetaSiguiente' => $siguiente
                ? $this->etiquetaRutinaHoy($user, $historial->entrenoEl($hoy), $hoy)
                : null,
            'ultimas' => $ultimas,
        ]);
    }

    /** "Toca hoy", "Hoy ya entrenaste · Próxima" o el próximo día de entrenamiento. */
    private function etiquetaRutinaHoy(User $user, bool $entrenoHoy, CarbonInterface $hoy): string
    {
        if ($entrenoHoy) {
            return 'Hoy ya entrenaste · Próxima';
        }
        if ($user->esDiaEntrenamiento($hoy)) {
            return 'Toca hoy';
        }

        $proximo = $user->proximoDiaEntrenamiento($hoy);

        return $proximo === null
            ? 'Siguiente rutina'
            : 'Próximo entreno · '.Formato::nombreDia($proximo);
    }
}
