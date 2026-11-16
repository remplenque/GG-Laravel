<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EjercicioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de "Arma tu rutina" (vista 03): el formulario y su validación.
 * Los de la propuesta (vista 04) y el guardado se suman en los pasos 3 y 4.
 */
class GenerarRutinaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EjercicioSeeder::class);
        $this->user = User::factory()->create([
            'objetivo' => 'Bajar de peso',
            'nivel' => 'Principiante',
            'dias_entrenamiento' => [0, 2, 4, 5],
        ]);
    }

    /** Un envío válido; cada test cambia solo lo que quiere probar. */
    private function datos(array $cambios = []): array
    {
        return array_merge([
            'objetivo' => 'Ganar músculo',
            'nivel' => 'Intermedio',
            'dias' => 4,
            'minutos' => 60,
            'equipamiento' => ['Barra', 'Mancuernas'],
            'molestias' => 'Molestia leve en el hombro derecho',
        ], $cambios);
    }

    public function test_el_formulario_muestra_las_opciones_de_la_config(): void
    {
        $respuesta = $this->actingAs($this->user)->get(route('rutinas.generar'));

        $respuesta->assertOk()->assertDontSee('En construcción');
        foreach (['Ganar músculo', 'Avanzado', '90 min', 'Kettlebell', 'Solo peso corporal'] as $opcion) {
            $respuesta->assertSee($opcion);
        }
    }

    public function test_precarga_objetivo_nivel_y_dias_del_perfil(): void
    {
        $html = $this->actingAs($this->user)->get(route('rutinas.generar'))->getContent();

        // El perfil dice Bajar de peso, Principiante y 4 días: esos radios van marcados.
        $this->assertMatchesRegularExpression('/value="Bajar de peso"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/value="Principiante"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/name="dias" value="4"[^>]*checked/', $html);
    }

    public function test_los_chips_de_equipamiento_vienen_marcados_por_defecto(): void
    {
        $html = $this->actingAs($this->user)->get(route('rutinas.generar'))->getContent();

        $this->assertMatchesRegularExpression('/name="equipamiento\[\]" value="Barra"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="equipamiento\[\]" value="Poleas"[^>]*checked/', $html);
    }

    public function test_un_envio_valido_pasa_la_validacion(): void
    {
        $this->actingAs($this->user)
            ->post(route('rutinas.proponer'), $this->datos())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('rutinas.generar'));
    }

    public function test_los_campos_obligatorios_dan_error(): void
    {
        $this->actingAs($this->user)
            ->from(route('rutinas.generar'))
            ->post(route('rutinas.proponer'), [])
            ->assertRedirect(route('rutinas.generar'))
            ->assertSessionHasErrors(['objetivo', 'nivel', 'dias', 'minutos', 'equipamiento']);
    }

    public function test_rechaza_valores_fuera_de_las_opciones(): void
    {
        $this->actingAs($this->user)
            ->post(route('rutinas.proponer'), $this->datos([
                'objetivo' => 'Ser astronauta',
                'nivel' => 'Experto',
                'dias' => 7,
                'minutos' => 30,
                'equipamiento' => ['Barra', 'Nave espacial'],
            ]))
            ->assertSessionHasErrors(['objetivo', 'nivel', 'dias', 'minutos', 'equipamiento.1']);
    }

    public function test_el_formulario_muestra_los_errores_en_la_pagina(): void
    {
        // followingRedirects: sigue la redirección y revisa la página que ve
        // el usuario, no solo la sesión.
        $this->actingAs($this->user)
            ->from(route('rutinas.generar'))
            ->followingRedirects()
            ->post(route('rutinas.proponer'), $this->datos(['equipamiento' => ['Nave espacial']]))
            ->assertOk()
            ->assertSee('Equipamiento no es una opción válida.');
    }

    public function test_molestias_es_opcional_pero_tiene_tope(): void
    {
        $this->actingAs($this->user)
            ->post(route('rutinas.proponer'), $this->datos(['molestias' => null]))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->user)
            ->post(route('rutinas.proponer'), $this->datos(['molestias' => str_repeat('a', 301)]))
            ->assertSessionHasErrors('molestias');
    }

    public function test_limita_los_envios_por_minuto(): void
    {
        // throttle:5,1: los primeros 5 pasan, el sexto recibe 429.
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->user)->post(route('rutinas.proponer'), $this->datos())->assertRedirect();
        }

        $this->actingAs($this->user)->post(route('rutinas.proponer'), $this->datos())->assertStatus(429);
    }
}
