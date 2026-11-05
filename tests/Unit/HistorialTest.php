<?php

namespace Tests\Unit;

use App\Models\Entrenamiento;
use App\Support\Historial;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class HistorialTest extends TestCase
{
    /** Miércoles. */
    private CarbonImmutable $hoy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hoy = CarbonImmutable::parse('2026-11-04 12:00');
    }

    private function historial(int ...$diasAtras): Historial
    {
        return new Historial(collect($diasAtras)->map(fn (int $d) => (new Entrenamiento)->forceFill([
            'nombre' => 'Push A',
            'inicio' => $this->hoy->subDays($d)->setTime(18, 0),
        ])));
    }

    public function test_la_racha_de_ayer_sigue_viva_si_hoy_no_entrena(): void
    {
        $this->assertSame(3, $this->historial(1, 2, 3, 5)->rachaActual($this->hoy));
    }

    public function test_entrenar_hoy_suma_a_la_racha(): void
    {
        $this->assertSame(2, $this->historial(0, 1, 3)->rachaActual($this->hoy));
    }

    public function test_sin_entrenar_ayer_ni_hoy_la_racha_es_cero(): void
    {
        $this->assertSame(0, $this->historial(2, 3)->rachaActual($this->hoy));
    }

    public function test_el_record_es_la_racha_mas_larga(): void
    {
        $this->assertSame(4, $this->historial(1, 3, 4, 5, 6, 9)->rachaRecord());
    }

    public function test_la_semana_va_de_lunes_a_domingo(): void
    {
        $semana = $this->historial(2, 7)->semana($this->hoy);

        $this->assertSame(['entrenado', 'descanso', 'hoy', 'futuro', 'futuro', 'futuro', 'futuro'], array_column($semana, 'estado'));
        $this->assertCount(1, $this->historial(2, 7)->deLaSemana($this->hoy));
    }
}
