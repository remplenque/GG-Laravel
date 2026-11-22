<?php

namespace App\Support;

use App\Ai\Agents\SugerenciaAgent;
use App\Models\Entrenamiento;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SugerenciaDia
{
    /** Horas que dura la sugerencia guardada si nada cambia. */
    private const HORAS_CACHE = 12;

    /** Minutos de espera antes de volver a intentar con la IA tras una falla. */
    private const MINUTOS_TRAS_FALLA = 10;

    /**
     * Genera la tarjeta con IA y vuelve a las reglas locales ante cualquier
     * problema de red, cuota o configuración.
     *
     * La respuesta se guarda en caché: Inicio es la pantalla que más se abre
     * y no tiene sentido gastar una llamada (y 1 a 2 segundos de espera) cada
     * vez. La llave cambia sola cuando hay algo nuevo que decir: otro día,
     * otra rutina que toca o un entrenamiento nuevo.
     *
     * @param  Collection<int, Entrenamiento>  $entrenamientos
     */
    public function generar(User $user, Rutina $rutina, Collection $entrenamientos, string $respaldo): string
    {
        $proveedores = app(ProveedoresIa::class);
        if (! $proveedores->configurada()) {
            return $respaldo;
        }

        $llave = implode(':', [
            'sugerencia',
            $user->getKey(),
            now()->toDateString(),
            $rutina->getKey(),
            $entrenamientos->first()?->getKey() ?? 0,
            $entrenamientos->first()?->updated_at?->timestamp ?? 0,
        ]);

        $guardada = Cache::get($llave);
        if (is_string($guardada) && $guardada !== '') {
            return $guardada;
        }

        $texto = $this->pedir($user, $rutina, $entrenamientos, $proveedores);
        if ($texto === null) {
            Cache::put($llave, $respaldo, now()->addMinutes(self::MINUTOS_TRAS_FALLA));

            return $respaldo;
        }

        Cache::put($llave, $texto, now()->addHours(self::HORAS_CACHE));

        return $texto;
    }

    /**
     * La llamada al agente; null si falla o responde vacío.
     *
     * @param  Collection<int, Entrenamiento>  $entrenamientos
     */
    private function pedir(User $user, Rutina $rutina, Collection $entrenamientos, ProveedoresIa $proveedores): ?string
    {
        try {
            $recientes = $entrenamientos->take(3)->values();
            $recientes->loadMissing('series.ejercicio');

            $respuesta = (new SugerenciaAgent)->prompt(
                $this->prompt($user, $rutina, $recientes),
                provider: $proveedores->disponibles(),
            );

            return trim((string) $respuesta) ?: null;
        } catch (Throwable $error) {
            report($error);

            return null;
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
