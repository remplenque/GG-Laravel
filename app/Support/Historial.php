<?php

namespace App\Support;

use App\Models\Entrenamiento;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Lo que se calcula a partir de los entrenamientos de una cuenta: racha,
 * totales de la semana y estado de cada día.
 */
class Historial
{
    /** Días (Y-m-d) con al menos un entrenamiento. */
    private array $diasEntrenados;

    /**
     * @param  Collection<int, Entrenamiento>  $entrenamientos  el más reciente primero
     */
    public function __construct(private Collection $entrenamientos)
    {
        $this->diasEntrenados = $entrenamientos
            ->mapWithKeys(fn (Entrenamiento $e) => [$e->inicio->toDateString() => true])
            ->all();
    }

    public function entrenoEl(CarbonInterface $fecha): bool
    {
        return isset($this->diasEntrenados[$fecha->toDateString()]);
    }

    /** Días seguidos entrenando. Si hoy aún no entrena, la racha de ayer sigue viva. */
    public function rachaActual(CarbonInterface $hoy): int
    {
        $cursor = CarbonImmutable::instance($hoy)->startOfDay();
        if (! $this->entrenoEl($cursor)) {
            $cursor = $cursor->subDay();
        }

        $racha = 0;
        while ($this->entrenoEl($cursor)) {
            $racha++;
            $cursor = $cursor->subDay();
        }

        return $racha;
    }

    /** La racha más larga de días seguidos entrenando. */
    public function rachaRecord(): int
    {
        $dias = array_keys($this->diasEntrenados);
        sort($dias);

        $record = 0;
        $actual = 0;
        $anterior = null;
        foreach ($dias as $dia) {
            $fecha = CarbonImmutable::parse($dia);
            $actual = $anterior !== null && $anterior->addDay()->isSameDay($fecha) ? $actual + 1 : 1;
            $record = max($record, $actual);
            $anterior = $fecha;
        }

        return $record;
    }

    /**
     * Entrenamientos de la semana (lunes a domingo) de $fecha.
     *
     * @return Collection<int, Entrenamiento>
     */
    public function deLaSemana(CarbonInterface $fecha): Collection
    {
        $desde = CarbonImmutable::instance($fecha)->startOfWeek(CarbonInterface::MONDAY);
        $hasta = $desde->addWeek();

        return $this->entrenamientos
            ->filter(fn (Entrenamiento $e) => $e->inicio->gte($desde) && $e->inicio->lt($hasta))
            ->values();
    }

    /**
     * Los 7 días de la semana de $hoy, de lunes a domingo, con su inicial y su
     * estado: entrenado, hoy (aún sin entrenar), descanso (ya pasó) o futuro.
     *
     * @return list<array{inicial: string, nombre: string, estado: string}>
     */
    public function semana(CarbonInterface $hoy): array
    {
        $hoy = CarbonImmutable::instance($hoy)->startOfDay();
        $lunes = $hoy->startOfWeek(CarbonInterface::MONDAY);

        return collect(range(0, 6))->map(function (int $i) use ($lunes, $hoy) {
            $dia = $lunes->addDays($i);

            return [
                'inicial' => Formato::inicialDia($dia),
                'nombre' => Formato::nombreDia($dia),
                'estado' => match (true) {
                    $this->entrenoEl($dia) => 'entrenado',
                    $dia->isSameDay($hoy) => 'hoy',
                    $dia->lt($hoy) => 'descanso',
                    default => 'futuro',
                },
            ];
        })->all();
    }
}
