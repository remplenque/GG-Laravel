<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Editar los datos del perfil y borrar la cuenta (Ley 21.719).
 */
class PerfilEdicionTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitados_van_al_login(): void
    {
        $this->get(route('perfil.editar'))->assertRedirect(route('login'));
        $this->put(route('perfil.actualizar'), [])->assertRedirect(route('login'));
        $this->delete(route('perfil.eliminar'))->assertRedirect(route('login'));
    }

    public function test_el_formulario_muestra_los_datos_actuales(): void
    {
        $user = User::factory()->create(['name' => 'Ana', 'objetivo' => 'Bajar de peso', 'peso_kg' => 64.5]);

        $this->actingAs($user)->get(route('perfil.editar'))
            ->assertOk()
            ->assertSee('Editar perfil')
            ->assertSee('value="Ana"', false)
            ->assertSee('value="64.5"', false)
            ->assertSee('Borrar mi cuenta');
    }

    public function test_guarda_los_datos_y_ordena_los_dias(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('perfil.actualizar'), [
            'name' => 'Ana',
            'apellido' => 'Pérez',
            'objetivo' => 'Ganar músculo',
            'nivel' => 'Intermedio',
            'peso_kg' => '70.5',
            'dias_entrenamiento' => ['4', '0', '2', '0'],
            'hora_entrenamiento' => '07:30',
        ])->assertRedirect(route('perfil'))->assertSessionHas('estado');

        $user->refresh();
        $this->assertSame('Ana', $user->name);
        $this->assertSame('Ganar músculo', $user->objetivo);
        $this->assertSame(70.5, $user->peso_kg);
        $this->assertSame([0, 2, 4], $user->dias_entrenamiento);
        $this->assertStringStartsWith('07:30', $user->hora_entrenamiento);
    }

    public function test_valida_opciones_y_rangos(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('perfil.editar'))->put(route('perfil.actualizar'), [
            'name' => '',
            'objetivo' => 'Ser astronauta',
            'peso_kg' => 900,
            'dias_entrenamiento' => [9],
            'hora_entrenamiento' => '25:99',
        ])->assertRedirect(route('perfil.editar'))
            ->assertSessionHasErrors(['name', 'objetivo', 'peso_kg', 'dias_entrenamiento.0', 'hora_entrenamiento']);
    }

    public function test_borrar_la_cuenta_pide_la_contrasena(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('perfil.editar'))
            ->delete(route('perfil.eliminar'), ['password' => 'incorrecta'])
            ->assertRedirect(route('perfil.editar'))
            ->assertSessionHasErrorsIn('eliminarCuenta', 'password');

        $this->assertModelExists($user);
    }

    public function test_borrar_la_cuenta_elimina_todo_lo_suyo(): void
    {
        $user = User::factory()->create();
        $user->rutinas()->create(['nombre' => 'Push A']);
        $user->entrenamientos()->create(['nombre' => 'Push A', 'inicio' => now(), 'fin' => now()->addHour()]);
        $conversacion = (string) Str::uuid();
        DB::table('agent_conversations')->insert([
            'id' => $conversacion, 'participant_type' => $user->getMorphClass(), 'participant_id' => $user->id,
            'title' => 'Coach', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('agent_conversation_messages')->insert([
            'id' => (string) Str::uuid(), 'conversation_id' => $conversacion, 'participant_type' => $user->getMorphClass(),
            'participant_id' => $user->id, 'agent' => 'CoachAgent', 'role' => 'user', 'content' => 'Hola',
            'attachments' => '[]', 'steps' => '[]', 'usage' => '[]', 'meta' => '[]', 'status' => 'completed',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete(route('perfil.eliminar'), ['password' => 'password'])
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertModelMissing($user);
        $this->assertDatabaseCount('rutinas', 0);
        $this->assertDatabaseCount('entrenamientos', 0);
        $this->assertDatabaseCount('agent_conversations', 0);
        $this->assertDatabaseCount('agent_conversation_messages', 0);
    }

    public function test_el_perfil_muestra_constancia_volumen_y_marcas(): void
    {
        $this->seed(DemoSeeder::class);
        $user = User::where('email', config('gogym.demo.email'))->firstOrFail();

        $this->actingAs($user)->get(route('perfil'))
            ->assertOk()
            ->assertSee('Constancia')
            ->assertSee('Volumen por semana')
            ->assertSee('Mejores marcas');
    }
}
