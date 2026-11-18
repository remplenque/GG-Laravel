<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\User;
use App\Support\PropuestaRutina;
use Database\Seeders\EjercicioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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

    public function test_un_envio_valido_lleva_a_la_propuesta(): void
    {
        $this->actingAs($this->user)
            ->post(route('rutinas.proponer'), $this->datos())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('rutinas.propuesta'));
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

    // ===================== Paso 3 · Lista permitida =====================

    public function test_permitidos_respeta_el_equipamiento(): void
    {
        $permitidos = app(PropuestaRutina::class)->permitidos($this->datos(['equipamiento' => ['Solo peso corporal']]));
        $equipos = Ejercicio::findMany($permitidos->pluck('id'))->pluck('equipo')->unique();

        $this->assertNotEmpty($permitidos);
        $this->assertEmpty($equipos->diff(['Sin equipo', 'Barra de dominadas', 'Paralelas']));
    }

    public function test_permitidos_entiende_los_prefijos_con_asterisco(): void
    {
        // 'Máquinas' → 'Máquina*': entran "Máquina Smith", "Máquina de curl femoral"…
        $permitidos = app(PropuestaRutina::class)->permitidos($this->datos(['equipamiento' => ['Máquinas']]));
        $equipos = Ejercicio::findMany($permitidos->pluck('id'))->pluck('equipo')->unique();

        $this->assertTrue($equipos->contains('Máquina Smith'));
        $this->assertTrue($equipos->every(fn ($e) => str_starts_with($e, 'Máquina') || in_array($e, ['Prensa de piernas', 'Pec deck'])));
    }

    public function test_permitidos_respeta_el_nivel_y_el_tope_por_grupo(): void
    {
        $permitidos = app(PropuestaRutina::class)->permitidos($this->datos(['nivel' => 'Principiante']));
        $ejercicios = Ejercicio::findMany($permitidos->pluck('id'));

        $this->assertSame(['Principiante'], $ejercicios->pluck('nivel')->unique()->values()->all());
        $this->assertLessThanOrEqual(15, $permitidos->countBy('grupo')->max());
    }

    // ===================== Paso 3 · Validar la propuesta =====================

    public function test_validar_acepta_la_propuesta_del_generador(): void
    {
        $servicio = app(PropuestaRutina::class);
        $permitidos = $servicio->permitidos($this->datos());

        $propuesta = $servicio->validar($servicio->generar($this->datos(), $permitidos), $permitidos, 4);

        $this->assertCount(4, $propuesta['dias']);
        $this->assertSame('Torso A', $propuesta['dias'][0]['nombre']);
    }

    public function test_validar_rechaza_ejercicios_inventados_y_dias_de_mas(): void
    {
        $servicio = app(PropuestaRutina::class);
        $permitidos = $servicio->permitidos($this->datos());
        $propuesta = $servicio->generar($this->datos(), $permitidos);

        $inventado = $propuesta;
        $inventado['dias'][0]['ejercicios'][0]['ejercicio_id'] = 'press-en-la-luna';

        $fueraDeRango = $propuesta;
        $fueraDeRango['dias'][1]['ejercicios'][0]['series'] = 12;

        foreach ([[$inventado, 4], [$fueraDeRango, 4], [$propuesta, 3]] as [$caso, $dias]) {
            try {
                $servicio->validar($caso, $permitidos, $dias);
                $this->fail('validar() debió rechazar la propuesta.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    // ===================== Paso 3 · Vista 04 =====================

    public function test_la_propuesta_muestra_el_plan_y_los_dias(): void
    {
        $this->actingAs($this->user)
            ->followingRedirects()
            ->post(route('rutinas.proponer'), $this->datos())
            ->assertOk()
            ->assertSee('Torso / Pierna · 4 días')
            ->assertSee('Intermedio · 60 min · Ganar músculo')
            ->assertSee(['Torso A', 'Pierna A', 'Torso B', 'Pierna B'])
            ->assertSee('Por qué esta rutina')
            ->assertSee('RPE 7');
    }

    public function test_sin_propuesta_en_la_sesion_vuelve_al_formulario(): void
    {
        $this->actingAs($this->user)->get(route('rutinas.propuesta'))->assertRedirect(route('rutinas.generar'));
        $this->actingAs($this->user)->post(route('rutinas.regenerar'))->assertRedirect(route('rutinas.generar'));
    }

    public function test_regenerar_cambia_los_ejercicios_y_mantiene_las_preferencias(): void
    {
        $this->actingAs($this->user)->post(route('rutinas.proponer'), $this->datos());
        $antes = session('rutina_ia');

        $this->actingAs($this->user)->post(route('rutinas.regenerar'))->assertRedirect(route('rutinas.propuesta'));
        $despues = session('rutina_ia');

        $this->assertSame($antes['preferencias'], $despues['preferencias']);
        $this->assertNotEquals($antes['propuesta']['dias'], $despues['propuesta']['dias']);
    }

    public function test_una_propuesta_invalida_vuelve_al_formulario_con_aviso(): void
    {
        // Simula que el generador (mañana, la IA) inventa un ejercicio:
        // partialMock reemplaza solo generar(); permitidos() y validar() son los reales.
        $this->partialMock(PropuestaRutina::class, fn ($mock) => $mock->shouldReceive('generar')->andReturn([
            'nombre' => 'Inventada',
            'explicacion' => 'x',
            'dias' => [['nombre' => 'Día 1', 'ejercicios' => [['ejercicio_id' => 'press-en-la-luna']]]],
        ]));

        $this->actingAs($this->user)
            ->followingRedirects()
            ->post(route('rutinas.proponer'), $this->datos())
            ->assertSee('El coach no pudo armar una rutina');

        $this->assertNull(session('rutina_ia'));
    }

    // ===================== Paso 4 · Guardar =====================

    public function test_guardar_crea_una_rutina_por_dia_en_la_cuenta(): void
    {
        $this->actingAs($this->user)->post(route('rutinas.proponer'), $this->datos());
        $propuesta = session('rutina_ia')['propuesta'];

        $this->actingAs($this->user)
            ->post(route('rutinas.guardar'))
            ->assertRedirect(route('rutinas.index'))
            ->assertSessionHas('estado', 'Guardamos 4 rutinas en tu rotación.');

        // Una rutina por día, con sus ejercicios en orden y el RPE en "carga".
        $rutinas = $this->user->rutinas()->with('ejercicios')->get();
        $this->assertSame(['Torso A', 'Pierna A', 'Torso B', 'Pierna B'], $rutinas->pluck('nombre')->all());

        $primera = $rutinas->first();
        $this->assertSame('Intermedio', $primera->nivel);
        $this->assertSame(60, $primera->minutos);
        $this->assertSame(
            collect($propuesta['dias'][0]['ejercicios'])->pluck('ejercicio_id')->all(),
            $primera->ejercicios->pluck('ejercicio_id')->all(),
        );
        $this->assertSame('RPE 7', $primera->ejercicios->first()->carga);

        // La propuesta ya no está en la sesión: no se puede guardar dos veces.
        $this->assertNull(session('rutina_ia'));
    }

    public function test_las_rutinas_guardadas_entran_a_la_rotacion(): void
    {
        $this->actingAs($this->user)->post(route('rutinas.proponer'), $this->datos());
        $this->actingAs($this->user)->post(route('rutinas.guardar'));

        // Sin historial, Inicio sugiere la primera rutina guardada.
        $this->actingAs($this->user)->get(route('inicio'))->assertSee('Torso A');
    }

    public function test_sin_reemplazar_conserva_las_rutinas_anteriores(): void
    {
        $vieja = $this->user->rutinas()->create(['nombre' => 'Mi rutina']);

        $this->actingAs($this->user)->post(route('rutinas.proponer'), $this->datos());
        $this->actingAs($this->user)->post(route('rutinas.guardar'));

        $this->assertModelExists($vieja);
        $this->assertSame(5, $this->user->rutinas()->count());
    }

    public function test_reemplazar_borra_las_anteriores_pero_no_el_historial(): void
    {
        $vieja = $this->user->rutinas()->create(['nombre' => 'Mi rutina']);
        $sesion = $this->user->entrenamientos()->create([
            'rutina_id' => $vieja->id,
            'nombre' => 'Mi rutina',
            'inicio' => now()->subDay(),
        ]);

        $this->actingAs($this->user)->post(route('rutinas.proponer'), $this->datos());
        $this->actingAs($this->user)->post(route('rutinas.guardar'), ['reemplazar' => '1']);

        $this->assertModelMissing($vieja);
        $this->assertSame(4, $this->user->rutinas()->count());
        // La sesión sigue, ahora como entrenamiento libre (rutina_id = null).
        $this->assertNull($sesion->fresh()->rutina_id);
    }

    public function test_guardar_sin_propuesta_vuelve_al_formulario(): void
    {
        $this->actingAs($this->user)
            ->post(route('rutinas.guardar'))
            ->assertRedirect(route('rutinas.generar'));

        $this->assertSame(0, $this->user->rutinas()->count());
    }
}
