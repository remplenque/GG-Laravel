<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InicioTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitados_van_al_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_cuenta_nueva_ve_inicio_vacio(): void
    {
        $user = User::factory()->create(['name' => 'Ana']);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Hola, Ana')
            ->assertSee('0 días seguidos')
            ->assertSee('Arma tu primera rutina')
            ->assertSee('Aún no terminas ningún entrenamiento.');
    }

    public function test_cuenta_demo_ve_racha_rutina_y_sesiones(): void
    {
        $this->seed(DemoSeeder::class);
        $user = User::where('email', config('gogym.demo.email'))->firstOrFail();

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('8 días seguidos')
            ->assertSee('Récord 8')
            // Tras "Core y cardio" la rotación vuelve a la primera rutina.
            ->assertSee('Push A · Pecho y hombro')
            ->assertSee('Core y cardio');
    }
}
