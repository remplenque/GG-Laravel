<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\Rutina;
use App\Models\User;
use Database\Seeders\EjercicioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntrenamientoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Rutina $rutina;

    private Ejercicio $ejercicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EjercicioSeeder::class);
        $this->user = User::factory()->create();
        $this->ejercicio = Ejercicio::query()->firstOrFail();
        $this->rutina = $this->user->rutinas()->create([
            'nombre' => 'Push de prueba',
            'nivel' => 'Principiante',
            'minutos' => 45,
        ]);
        $this->rutina->ejercicios()->create([
            'ejercicio_id' => $this->ejercicio->id,
            'orden' => 0,
            'series' => 3,
            'repeticiones' => 10,
            'descanso_seg' => 90,
        ]);
    }

    private function datos(array $cambios = []): array
    {
        return array_replace([
            'rutina_id' => $this->rutina->id,
            'nombre' => 'Push de prueba',
            'inicio' => now()->subHour()->format('Y-m-d\TH:i'),
            'duracion' => 50,
            'ejercicios' => [[
                'ejercicio_id' => $this->ejercicio->id,
                'series' => [
                    ['kg' => 40, 'reps' => 10, 'rpe' => 7],
                    ['kg' => 42.5, 'reps' => 8, 'rpe' => 8],
                    ['kg' => null, 'reps' => null, 'rpe' => null],
                ],
            ]],
        ], $cambios);
    }

    public function test_historial_y_registro_ya_no_estan_en_construccion(): void
    {
        $this->actingAs($this->user)->get(route('entrenamientos.index'))
            ->assertOk()->assertDontSee('En construcción')->assertSee('Aún no registras entrenamientos');

        $this->actingAs($this->user)->get(route('entrenamientos.create', ['rutina' => $this->rutina]))
            ->assertOk()->assertDontSee('En construcción')->assertSee($this->ejercicio->nombre);
    }

    public function test_guarda_una_sesion_con_sus_series(): void
    {
        $this->actingAs($this->user)
            ->post(route('entrenamientos.store'), $this->datos())
            ->assertRedirect(route('entrenamientos.index'))
            ->assertSessionHas('estado');

        $entrenamiento = $this->user->entrenamientos()->with('series')->firstOrFail();
        $this->assertSame($this->rutina->id, $entrenamiento->rutina_id);
        $this->assertSame(50, $entrenamiento->duracion_minutos);
        $this->assertCount(2, $entrenamiento->series);
        $this->assertSame(42.5, $entrenamiento->series->last()->kg);
    }

    public function test_valida_datos_y_exige_una_serie_completa(): void
    {
        $datos = $this->datos([
            'nombre' => '',
            'duracion' => 0,
            'ejercicios' => [[
                'ejercicio_id' => $this->ejercicio->id,
                'series' => [['kg' => 30, 'reps' => null, 'rpe' => 11]],
            ]],
        ]);

        $this->actingAs($this->user)->post(route('entrenamientos.store'), $datos)
            ->assertSessionHasErrors(['nombre', 'duracion', 'ejercicios.0.series', 'ejercicios.0.series.0.rpe']);

        $this->assertDatabaseCount('entrenamientos', 0);
    }

    public function test_actualiza_la_sesion_y_reemplaza_sus_series(): void
    {
        $this->actingAs($this->user)->post(route('entrenamientos.store'), $this->datos());
        $entrenamiento = $this->user->entrenamientos()->firstOrFail();

        $this->actingAs($this->user)->put(
            route('entrenamientos.update', $entrenamiento),
            $this->datos([
                'nombre' => 'Push corregido',
                'ejercicios' => [[
                    'ejercicio_id' => $this->ejercicio->id,
                    'series' => [['kg' => 50, 'reps' => 6, 'rpe' => 9]],
                ]],
            ]),
        )->assertRedirect(route('entrenamientos.index'));

        $this->assertSame('Push corregido', $entrenamiento->fresh()->nombre);
        $this->assertDatabaseCount('entrenamiento_series', 1);
        $this->assertDatabaseHas('entrenamiento_series', ['kg' => 50, 'reps' => 6, 'rpe' => 9]);
    }

    public function test_elimina_una_sesion_y_sus_series(): void
    {
        $this->actingAs($this->user)->post(route('entrenamientos.store'), $this->datos());
        $entrenamiento = $this->user->entrenamientos()->firstOrFail();

        $this->actingAs($this->user)
            ->delete(route('entrenamientos.destroy', $entrenamiento))
            ->assertRedirect(route('entrenamientos.index'));

        $this->assertDatabaseCount('entrenamientos', 0);
        $this->assertDatabaseCount('entrenamiento_series', 0);
    }

    public function test_una_cuenta_no_puede_editar_ni_borrar_sesiones_ajenas(): void
    {
        $ajeno = User::factory()->create()->entrenamientos()->create([
            'nombre' => 'Sesión privada',
            'inicio' => now(),
            'fin' => now()->addHour(),
        ]);

        $this->actingAs($this->user)->get(route('entrenamientos.edit', $ajeno))->assertForbidden();
        $this->actingAs($this->user)->delete(route('entrenamientos.destroy', $ajeno))->assertForbidden();
        $this->assertModelExists($ajeno);
    }
}
