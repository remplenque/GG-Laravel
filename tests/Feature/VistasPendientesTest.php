<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EjercicioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VistasPendientesTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_vistas_pendientes_responden(): void
    {
        $this->seed(EjercicioSeeder::class);
        $user = User::factory()->create();

        foreach ([
            route('entrenamientos.index'),
            route('entrenamientos.create'),
        ] as $url) {
            $this->actingAs($user)->get($url)->assertOk()->assertSee('En construcción');
        }
    }
}
