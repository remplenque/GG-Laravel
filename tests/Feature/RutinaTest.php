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
}
