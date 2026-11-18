<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EjercicioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests de "Explorar en wger" (GET /ejercicios/wger).
 *
 * Http::fake() reemplaza las llamadas a wger por respuestas inventadas: los
 * tests no dependen de internet ni de que wger esté arriba, y se puede
 * probar también qué pasa cuando falla.
 */
class WgerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Cache::flush();
    }

    /** Respuesta de wger con tres ejercicios, en la forma real de la API. */
    private function respuestaWger(): array
    {
        return ['results' => [
            [
                // Sin imagen y solo en inglés.
                'id' => 1,
                'category' => ['name' => 'Legs'],
                'muscles' => [['name' => 'Quadriceps femoris', 'name_en' => 'Quads']],
                'equipment' => [['name' => 'Barbell']],
                'images' => [],
                'translations' => [
                    ['language' => 2, 'name' => 'Back Squat', 'description' => '<p>Squat&nbsp;down.</p><ol><li>Keep your back straight</li></ol>'],
                ],
            ],
            [
                // Con imagen y en español.
                'id' => 2,
                'category' => ['name' => 'Chest'],
                'muscles' => [['name' => 'Pectoralis major', 'name_en' => 'Chest']],
                'equipment' => [['name' => 'Dumbbell']],
                'images' => [['image' => 'https://wger.de/media/press.png', 'is_main' => true]],
                'translations' => [
                    ['language' => 2, 'name' => 'Dumbbell Press', 'description' => ''],
                    ['language' => 4, 'name' => 'Press con mancuernas', 'description' => '<p>Empuja hacia arriba.</p>'],
                ],
            ],
            [
                // Sin nombre en español ni inglés: se descarta.
                'id' => 3,
                'category' => ['name' => 'Abs'],
                'muscles' => [],
                'equipment' => [],
                'images' => [],
                'translations' => [['language' => 7, 'name' => 'Abdominal', 'description' => '']],
            ],
        ]];
    }

    public function test_muestra_los_ejercicios_traducidos(): void
    {
        Http::fake(['wger.de/*' => Http::response($this->respuestaWger())]);

        $respuesta = $this->actingAs($this->user)->get(route('ejercicios.wger'));

        $respuesta->assertOk()
            ->assertSee('2 ejercicios')
            // Español si existe; si no, inglés con su etiqueta.
            ->assertSee('Press con mancuernas')
            ->assertSee('Back Squat')
            ->assertSee('En inglés')
            // Categoría, músculo y equipo traducidos con las tablas.
            ->assertSee(['Piernas', 'Cuádriceps', 'Barra', 'Pecho', 'Mancuernas'])
            ->assertDontSee('Abdominal');

        // Los que tienen imagen van primero.
        $this->assertSame('Press con mancuernas', $respuesta->viewData('ejercicios')->first()['nombre']);
    }

    public function test_pasa_la_descripcion_de_html_a_texto(): void
    {
        Http::fake(['wger.de/*' => Http::response($this->respuestaWger())]);

        $sentadilla = $this->actingAs($this->user)->get(route('ejercicios.wger'))
            ->viewData('ejercicios')->firstWhere('id', 1);

        $this->assertSame("Squat down.\n• Keep your back straight", $sentadilla['descripcion']);
    }

    public function test_filtra_por_categoria(): void
    {
        Http::fake(['wger.de/*' => Http::response($this->respuestaWger())]);

        $ejercicios = $this->actingAs($this->user)->get(route('ejercicios.wger', ['categoria' => 'Pecho']))
            ->viewData('ejercicios');

        $this->assertSame(['Press con mancuernas'], $ejercicios->pluck('nombre')->all());
    }

    public function test_guarda_la_respuesta_en_cache(): void
    {
        Http::fake(['wger.de/*' => Http::response($this->respuestaWger())]);

        $this->actingAs($this->user)->get(route('ejercicios.wger'));
        $this->actingAs($this->user)->get(route('ejercicios.wger'));

        // Dos visitas, una sola llamada a wger.
        Http::assertSentCount(1);
    }

    public function test_si_wger_falla_muestra_un_aviso_y_no_lo_guarda_en_cache(): void
    {
        Http::fake(['wger.de/*' => Http::response('Error', 500)]);

        $this->actingAs($this->user)->get(route('ejercicios.wger'))
            ->assertOk()
            ->assertSee('No pudimos conectar con wger');

        // El error no quedó en caché: la próxima visita vuelve a intentar.
        $this->assertFalse(Cache::has('wger.ejercicios'));
    }

    public function test_la_biblioteca_enlaza_a_wger_y_la_ruta_no_choca_con_el_detalle(): void
    {
        Http::fake(['wger.de/*' => Http::response($this->respuestaWger())]);
        $this->seed(EjercicioSeeder::class);

        $this->actingAs($this->user)->get(route('ejercicios.index'))->assertSee(route('ejercicios.wger'));
        // /ejercicios/wger es la vista de wger, no el detalle de un ejercicio "wger".
        $this->actingAs($this->user)->get('/ejercicios/wger')->assertOk()->assertSee('Explorar en wger');
    }
}
