<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitados_van_al_login(): void
    {
        $this->get('/perfil')->assertRedirect(route('login'));
    }

    public function test_cuenta_nueva_ve_sus_datos_y_estados_vacios(): void
    {
        $user = User::factory()->create([
            'name' => 'Ana',
            'apellido' => 'Pérez',
            'objetivo' => null,
            'dias_entrenamiento' => null,
        ]);

        $this->actingAs($user)->get('/perfil')
            ->assertOk()
            ->assertSee('Ana Pérez')
            ->assertSee('Aún no tienes días programados')
            ->assertSee('Sin definir');
    }

    public function test_cuenta_demo_ve_plan_datos_y_progreso(): void
    {
        $this->seed(DemoSeeder::class);
        $user = User::where('email', config('gogym.demo.email'))->firstOrFail();

        $this->actingAs($user)->get('/perfil')
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertSee('Próximos entrenamientos')
            ->assertSee('Push A')
            ->assertSee('Ganar músculo')
            ->assertSee('Tu progreso este mes')
            ->assertDontSee('En construcción');
    }
}
