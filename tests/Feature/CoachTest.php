<?php

namespace Tests\Feature;

use App\Ai\Agents\CoachAgent;
use App\Models\User;
use App\Support\Coach;
use Database\Seeders\EjercicioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Tests del Coach IA (vista 02): el chat, la validación, la conversación en
 * la sesión, las instrucciones del agente y el manejo de errores de la IA.
 */
class CoachTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EjercicioSeeder::class);
        $this->user = User::factory()->create([
            'name' => 'Vicente',
            'email' => 'vicente@gogym.app',
            'objetivo' => 'Ganar músculo',
            'nivel' => 'Intermedio',
        ]);
    }

    public function test_sin_sesion_redirige_al_login(): void
    {
        $this->get(route('coach'))->assertRedirect(route('login'));
        $this->post(route('coach.preguntar'), ['pregunta' => 'Hola'])->assertRedirect(route('login'));
    }

    public function test_la_conversacion_vacia_saluda_y_sugiere_preguntas(): void
    {
        $this->actingAs($this->user)->get(route('coach'))
            ->assertOk()
            ->assertDontSee('En construcción')
            ->assertSee('Hola, Vicente')
            ->assertSee('¿Cuánto descanso entre series?')
            ->assertSee('Orientación general');
    }

    public function test_la_pregunta_de_la_url_queda_escrita_en_el_campo(): void
    {
        $this->actingAs($this->user)
            ->get(route('coach', ['pregunta' => '¿Cuál es el error más común en peso muerto rumano?']))
            ->assertOk()
            ->assertSee('value="¿Cuál es el error más común en peso muerto rumano?"', false);
    }

    public function test_preguntar_guarda_la_pregunta_y_la_respuesta(): void
    {
        $this->actingAs($this->user)
            ->post(route('coach.preguntar'), ['pregunta' => '¿Cuánto descanso entre series?'])
            ->assertRedirect(route('coach'));

        $mensajes = session('coach.mensajes');
        $this->assertCount(2, $mensajes);
        $this->assertSame(['usuario', 'coach'], array_column($mensajes, 'rol'));
        // Ganar músculo → descansos largos en los ejercicios grandes.
        $this->assertStringContainsString('2 a 3 minutos', $mensajes[1]['texto']);

        $this->actingAs($this->user)->get(route('coach'))
            ->assertSee('¿Cuánto descanso entre series?')
            ->assertSee('<strong>2 a 3 minutos</strong>', false);
    }

    public function test_la_pregunta_es_obligatoria_y_tiene_tope(): void
    {
        $this->actingAs($this->user)->from(route('coach'))
            ->post(route('coach.preguntar'), ['pregunta' => '   '])
            ->assertRedirect(route('coach'))
            ->assertSessionHasErrors('pregunta');

        $this->actingAs($this->user)
            ->post(route('coach.preguntar'), ['pregunta' => str_repeat('a', Coach::MAX_PREGUNTA + 1)])
            ->assertSessionHasErrors('pregunta');

        $this->assertNull(session('coach.mensajes'));
    }

    public function test_si_nombra_un_ejercicio_ofrece_su_ficha(): void
    {
        $this->actingAs($this->user)
            ->post(route('coach.preguntar'), ['pregunta' => '¿Cuál es el error más común en peso muerto rumano?']);

        // Gana el nombre más largo que calza ("peso muerto rumano", no "peso muerto").
        $this->assertSame('romanian-deadlift', session('coach.mensajes')[1]['ejercicio_id']);

        $this->actingAs($this->user)->get(route('coach'))
            ->assertSee(route('ejercicios.show', 'romanian-deadlift'))
            ->assertSee('Ver ficha · Peso Muerto Rumano');
    }

    public function test_ante_dolor_recomienda_un_profesional(): void
    {
        $this->actingAs($this->user)
            ->post(route('coach.preguntar'), ['pregunta' => 'Me duele el hombro en el press de banca']);

        $this->assertStringContainsString('profesional de la salud', session('coach.mensajes')[1]['texto']);
    }

    public function test_el_html_de_la_respuesta_no_se_imprime(): void
    {
        $this->actingAs($this->user)
            ->withSession(['coach.mensajes' => [
                ['rol' => 'usuario', 'texto' => '<b>hola</b>', 'ejercicio_id' => null],
                ['rol' => 'coach', 'texto' => 'Hola <script>alert(1)</script>', 'ejercicio_id' => null],
            ]])
            ->get(route('coach'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;b&gt;hola&lt;/b&gt;', false);
    }

    public function test_si_la_ia_falla_muestra_un_aviso_y_no_guarda_nada(): void
    {
        $this->mock(Coach::class, function ($mock) {
            $mock->shouldReceive('ejercicioMencionado')->andReturnNull();
            $mock->shouldReceive('responder')->andThrow(new RuntimeException('Groq sin cuota'));
        });

        $this->actingAs($this->user)->from(route('coach'))
            ->post(route('coach.preguntar'), ['pregunta' => '¿Cuándo subo el peso?'])
            ->assertRedirect(route('coach'))
            ->assertSessionHas('error')
            ->assertSessionHasInput('pregunta', '¿Cuándo subo el peso?');

        $this->assertNull(session('coach.mensajes'));
    }

    public function test_nueva_conversacion_borra_la_actual(): void
    {
        $this->actingAs($this->user)
            ->post(route('coach.preguntar'), ['pregunta' => '¿Cuándo subo el peso?']);
        $this->assertCount(2, session('coach.mensajes'));

        $this->actingAs($this->user)->delete(route('coach.nueva'))->assertRedirect(route('coach'));
        $this->assertNull(session('coach.mensajes'));
    }

    public function test_las_instrucciones_fijan_el_rol_y_el_contexto_no_lleva_datos_personales(): void
    {
        $coach = app(Coach::class);

        $this->assertStringContainsString('Solo hablas de entrenamiento', $coach->instrucciones());
        $this->assertStringContainsString('profesional de la salud', $coach->instrucciones());

        $contexto = $coach->contexto($this->user);
        $this->assertStringContainsString('Intermedio', $contexto);
        $this->assertStringContainsString('Ganar músculo', $contexto);
        $this->assertStringNotContainsString('Vicente', $contexto);
        $this->assertStringNotContainsString('vicente@gogym.app', $contexto);
    }

    public function test_con_clave_usa_el_agente_y_guarda_la_conversacion_en_la_cuenta(): void
    {
        config(['ai.providers.groq.key' => 'clave-de-prueba']);
        CoachAgent::fake(['Respuesta generada por **IA**.']);

        $this->actingAs($this->user)
            ->post(route('coach.preguntar'), ['pregunta' => '¿Cuándo subo el peso?'])
            ->assertRedirect(route('coach'));

        $this->assertIsString(session('coach.conversacion_id'));
        $this->assertDatabaseCount('agent_conversations', 1);
        $this->assertDatabaseCount('agent_conversation_messages', 2);
        $this->actingAs($this->user)->get(route('coach'))
            ->assertSee('Respuesta generada por <strong>IA</strong>.', false);

        $this->actingAs($this->user)->delete(route('coach.nueva'));
        $this->assertDatabaseCount('agent_conversations', 0);
        $this->assertDatabaseCount('agent_conversation_messages', 0);
    }

    public function test_con_javascript_responde_json_con_el_html_de_los_mensajes(): void
    {
        $respuesta = $this->actingAs($this->user)
            ->postJson(route('coach.preguntar'), ['pregunta' => '¿Cuánto descanso entre series?'])
            ->assertOk()
            ->assertJsonStructure(['html']);

        $html = $respuesta->json('html');
        $this->assertStringContainsString('¿Cuánto descanso entre series?', $html);
        $this->assertStringContainsString('<strong>2 a 3 minutos</strong>', $html);
        $this->assertCount(2, session('coach.mensajes'));
    }

    public function test_con_javascript_valida_y_avisa_si_la_ia_falla(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('coach.preguntar'), ['pregunta' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pregunta');

        $this->mock(Coach::class, function ($mock) {
            $mock->shouldReceive('ejercicioMencionado')->andReturn(null);
            $mock->shouldReceive('responder')->andThrow(new RuntimeException('Cuota agotada'));
        });

        $this->actingAs($this->user)
            ->postJson(route('coach.preguntar'), ['pregunta' => 'Hola'])
            ->assertStatus(503)
            ->assertJsonPath('message', 'El coach no pudo responder ahora. Espera un momento y vuelve a intentarlo.');
    }
}
