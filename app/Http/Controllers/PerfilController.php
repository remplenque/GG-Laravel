<?php

namespace App\Http\Controllers;

use App\Models\Rutina;
use App\Models\User;
use App\Support\Historial;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerfilController extends Controller
{
    /** Perfil, plan de entrenamiento y resumen de progreso de la cuenta. */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $hoy = now()->toImmutable();
        $entrenamientos = $user->entrenamientos()->get();
        $rutinas = $user->rutinas()->with('ejercicios.ejercicio')->get();
        $historial = new Historial($entrenamientos);
        $entrenamientosDelMes = $entrenamientos->filter(
            fn ($entrenamiento) => $entrenamiento->inicio->isSameMonth($hoy)
        );

        return view('perfil.show', [
            'user' => $user,
            'hoy' => $hoy,
            'proximos' => $this->proximosEntrenamientos($user, $rutinas, $entrenamientos, $hoy),
            'progreso' => [
                'sesiones' => $entrenamientosDelMes->count(),
                'minutos' => $entrenamientosDelMes->sum('duracion_minutos'),
                'racha' => $historial->rachaActual($hoy),
            ],
        ]);
    }

    /**
     * Arma las tres próximas fechas del plan y rota las rutinas disponibles.
     * Si hoy ya se entrenó, el calendario parte desde el siguiente día elegido.
     *
     * @param  Collection<int, Rutina>  $rutinas
     * @param  Collection<int, \App\Models\Entrenamiento>  $entrenamientos
     * @return list<array{fecha: CarbonInterface, rutina: ?Rutina}>
     */
    private function proximosEntrenamientos(
        User $user,
        Collection $rutinas,
        Collection $entrenamientos,
        CarbonInterface $hoy,
    ): array {
        $disponibles = $rutinas
            ->filter(fn (Rutina $rutina) => $rutina->ejercicios->isNotEmpty())
            ->values();
        $siguiente = Rutina::siguiente($rutinas, $entrenamientos);
        $indiceInicial = $siguiente === null
            ? 0
            : $disponibles->search(fn (Rutina $rutina) => $rutina->is($siguiente));
        $indiceInicial = $indiceInicial === false ? 0 : $indiceInicial;
        $entrenoHoy = $entrenamientos->contains(
            fn ($entrenamiento) => $entrenamiento->inicio->isSameDay($hoy)
        );

        $proximos = [];
        for ($desplazamiento = 0; $desplazamiento <= 21 && count($proximos) < 3; $desplazamiento++) {
            $fecha = $hoy->toImmutable()->startOfDay()->addDays($desplazamiento);
            if (! $user->esDiaEntrenamiento($fecha) || ($desplazamiento === 0 && $entrenoHoy)) {
                continue;
            }

            $proximos[] = [
                'fecha' => $fecha,
                'rutina' => $disponibles->isEmpty()
                    ? null
                    : $disponibles[($indiceInicial + count($proximos)) % $disponibles->count()],
            ];
        }

        return $proximos;
    }
}
