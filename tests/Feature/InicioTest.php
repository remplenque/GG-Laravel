<?php

namespace Tests\Feature;

use App\Ai\Agents\SugerenciaAgent;
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

    public function test_con_clave_la_sugerencia_del_dia_viene_del_agente(): void
    {
        $this->seed(DemoSeeder::class);
        $user = User::where('email', config('gogym.demo.email'))->firstOrFail();
        config(['ai.providers.groq.key' => 'clave-de-prueba']);
        SugerenciaAgent::fake(['Mantén la carga de la última sesión y completa todas las repeticiones.']);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Mantén la carga de la última sesión y completa todas las repeticiones.');

        SugerenciaAgent::assertPromptedTimes(1);
    }

    public function test_la_sugerencia_del_dia_se_guarda_en_cache(): void
    {
        $this->seed(DemoSeeder::class);
        $user = User::where('email', config('gogym.demo.email'))->firstOrFail();
        config(['ai.providers.groq.key' => 'clave-de-prueba']);
        SugerenciaAgent::fake(['Sube 2,5 kg en el press de banca.']);

        $this->actingAs($user)->get('/')->assertSee('Sube 2,5 kg en el press de banca.');
        $this->actingAs($user)->get('/')->assertSee('Sube 2,5 kg en el press de banca.');

        SugerenciaAgent::assertPromptedTimes(1);
    }

    public function test_cuenta_sin_datos_ve_el_llamado_a_completar_el_perfil(): void
    {
        $user = User::factory()->create(['name' => 'Ana']);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Completa tu perfil')
            ->assertSee('Meta semanal');
    }

    public function test_las_paginas_de_error_usan_el_diseno_de_la_app(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/rutinas/999999')
            ->assertNotFound()
            ->assertSee('No encontramos esta página');
    }
}
