<?php

namespace Tests\Feature;

use App\Models\Rutina;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RutinaTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_las_rutinas_de_la_cuenta(): void
    {
        $this->seed(DemoSeeder::class);
        $user = User::where('email', config('gogym.demo.email'))->firstOrFail();

        $this->actingAs($user)->get(route('rutinas.index'))
            ->assertOk()
            ->assertSee('4 rutinas · rotan en orden')
            ->assertSeeInOrder(['Push A', 'Pull A', 'Legs A', 'Core y cardio']);
    }

    public function test_muestra_el_detalle_con_sus_ejercicios(): void
    {
        $this->seed(DemoSeeder::class);
        $rutina = Rutina::where('nombre', 'Push A')->firstOrFail();

        $this->actingAs($rutina->user)->get(route('rutinas.show', $rutina))
            ->assertOk()
            ->assertSee('4 × 8 · 60 kg')
            ->assertSee('descanso 2 min')
            ->assertSee('Empezar rutina');
    }

    public function test_no_se_ve_la_rutina_de_otra_cuenta(): void
    {
        $this->seed(DemoSeeder::class);
        $rutina = Rutina::firstOrFail();

        $this->actingAs(User::factory()->create())
            ->get(route('rutinas.show', $rutina))
            ->assertForbidden();
    }

    public function test_cuenta_sin_rutinas_ve_estado_vacio(): void
    {
        $this->actingAs(User::factory()->create())->get(route('rutinas.index'))
            ->assertOk()
            ->assertSee('Aún no tienes rutinas');
    }

    // ===================== Editar y borrar =====================

    public function test_el_formulario_de_edicion_muestra_los_ejercicios(): void
    {
        $this->seed(DemoSeeder::class);
        $rutina = Rutina::where('nombre', 'Push A')->firstOrFail();

        $this->actingAs($rutina->user)->get(route('rutinas.editar', $rutina))
            ->assertOk()
            ->assertSee('Editar rutina')
            ->assertSee($rutina->ejercicios->first()->ejercicio->nombre);
    }

    public function test_actualizar_cambia_numeros_y_quita_ejercicios(): void
    {
        $this->seed(DemoSeeder::class);
        $rutina = Rutina::where('nombre', 'Push A')->with('ejercicios')->firstOrFail();
        [$primero, $segundo] = $rutina->ejercicios;

        $this->actingAs($rutina->user)
            ->put(route('rutinas.actualizar', $rutina), [
                'nombre' => 'Empuje',
                'ejercicios' => [
                    $primero->id => ['series' => 5, 'repeticiones' => 5, 'descanso_seg' => 180],
                    $segundo->id => ['series' => 3, 'repeticiones' => 10, 'descanso_seg' => 90, 'quitar' => '1'],
                ],
            ])
            ->assertRedirect(route('rutinas.show', $rutina));

        $this->assertSame('Empuje', $rutina->fresh()->nombre);
        $this->assertSame([5, 5, 180], [$primero->fresh()->series, $primero->fresh()->repeticiones, $primero->fresh()->descanso_seg]);
        $this->assertModelMissing($segundo);
    }

    public function test_actualizar_valida_los_rangos(): void
    {
        $this->seed(DemoSeeder::class);
        $rutina = Rutina::where('nombre', 'Push A')->with('ejercicios')->firstOrFail();

        $this->actingAs($rutina->user)
            ->put(route('rutinas.actualizar', $rutina), [
                'nombre' => '',
                'ejercicios' => [$rutina->ejercicios[0]->id => ['series' => 0, 'repeticiones' => 8, 'descanso_seg' => 90]],
            ])
            ->assertSessionHasErrors(['nombre', 'ejercicios.'.$rutina->ejercicios[0]->id.'.series']);
    }

    public function test_no_se_puede_editar_ni_borrar_la_rutina_de_otra_cuenta(): void
    {
        $this->seed(DemoSeeder::class);
        $rutina = Rutina::firstOrFail();
        $otra = User::factory()->create();

        $this->actingAs($otra)->get(route('rutinas.editar', $rutina))->assertForbidden();
        $this->actingAs($otra)->put(route('rutinas.actualizar', $rutina), ['nombre' => 'Mía'])->assertForbidden();
        $this->actingAs($otra)->delete(route('rutinas.eliminar', $rutina))->assertForbidden();
        $this->assertModelExists($rutina);
    }

    public function test_borrar_quita_la_rutina_y_conserva_el_historial(): void
    {
        $this->seed(DemoSeeder::class);
        $rutina = Rutina::where('nombre', 'Push A')->firstOrFail();
        $sesiones = $rutina->user->entrenamientos()->where('rutina_id', $rutina->id)->count();

        $this->actingAs($rutina->user)
            ->delete(route('rutinas.eliminar', $rutina))
            ->assertRedirect(route('rutinas.index'))
            ->assertSessionHas('estado', 'Borramos Push A.');

        $this->assertModelMissing($rutina);
        // Las sesiones siguen (ahora sin rutina): el historial no se pierde.
        $this->assertGreaterThan(0, $sesiones);
        $this->assertSame($sesiones, $rutina->user->entrenamientos()->where('nombre', 'Push A')->whereNull('rutina_id')->count());
    }

    // ===================== Agregar desde el detalle del ejercicio =====================

    public function test_agregar_suma_el_ejercicio_al_final_de_la_rutina(): void
    {
        $this->seed(DemoSeeder::class);
        $rutina = Rutina::where('nombre', 'Push A')->firstOrFail();
        $antes = $rutina->ejercicios()->count();

        $this->actingAs($rutina->user)
            ->post(route('ejercicios.agregar', 'lateral-raise'), ['rutina_id' => $rutina->id])
            ->assertRedirect(route('rutinas.show', $rutina));

        $ultimo = $rutina->ejercicios()->get()->last();
        $this->assertSame($antes + 1, $rutina->ejercicios()->count());
        $this->assertSame('lateral-raise', $ultimo->ejercicio_id);
        $this->assertSame('3 × 10', $ultimo->resumen);
    }

    public function test_no_se_puede_agregar_a_una_rutina_ajena(): void
    {
        $this->seed(DemoSeeder::class);
        $rutina = Rutina::firstOrFail();
        $antes = $rutina->ejercicios()->count();

        $this->actingAs(User::factory()->create())
            ->post(route('ejercicios.agregar', 'lateral-raise'), ['rutina_id' => $rutina->id])
            ->assertSessionHasErrors('rutina_id');

        $this->assertSame($antes, $rutina->ejercicios()->count());
    }
}
