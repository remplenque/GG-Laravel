<?php

namespace App\Support;

use App\Ai\Agents\SugerenciaAgent;
use App\Models\Entrenamiento;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class SugerenciaDia
{
    /**
     * Genera la tarjeta con IA y vuelve a las reglas locales ante cualquier
     * problema de red, cuota o configuración.
     *
     * @param  Collection<int, Entrenamiento>  $entrenamientos
     */
    public function generar(User $user, Rutina $rutina, Collection $entrenamientos, string $respaldo): string
    {
        $proveedores = app(ProveedoresIa::class);
        if (! $proveedores->configurada()) {
            return $respaldo;
        }

        try {
            $recientes = $entrenamientos->take(3)->values();
            $recientes->loadMissing('series.ejercicio');

            $respuesta = (new SugerenciaAgent)->prompt(
                $this->prompt($user, $rutina, $recientes),
                provider: $proveedores->disponibles(),
            );

            return trim((string) $respuesta) ?: $respaldo;
        } catch (Throwable $error) {
            report($error);

            return $respaldo;
        }
    }

    /** @param Collection<int, Entrenamiento> $recientes */
    private function prompt(User $user, Rutina $rutina, Collection $recientes): string
    {
        $historial = $recientes->map(function (Entrenamiento $entrenamiento): string {
            $series = $entrenamiento->series
                ->groupBy('ejercicio_id')
                ->map(function ($series): string {
                    $nombre = $series->first()->ejercicio?->nombre ?? 'Ejercicio';
                    $mejor = $series->sortByDesc('kg')->first();
                    $marca = $mejor?->kg !== null
                        ? $this->kg((float) $mejor->kg).' kg × '.($mejor->reps ?? '?')
                        : $series->count().' series';

                    return "{$nombre}: {$marca}";
                })->implode('; ');

            return $entrenamiento->inicio->toDateString()." | {$entrenamiento->nombre} | {$series}";
        })->implode("\n");

        if ($historial === '') {
            $historial = 'Sin sesiones anteriores.';
        }

        return <<<TEXTO
            Nivel: {$user->nivel}
            Objetivo: {$user->objetivo}
            Próxima rutina: {$rutina->nombre}
            Historial reciente:
            {$historial}
            TEXTO;
    }

    private function kg(float $kg): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format($kg, 2, '.', ''), '0'), '.'));
    }
}
