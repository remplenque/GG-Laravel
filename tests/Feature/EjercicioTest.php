<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\User;
use Database\Seeders\EjercicioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de la Biblioteca de ejercicios (GET /ejercicios).
 *
 * Cada test parte con una base de datos vacía (RefreshDatabase), carga el
 * catálogo RepDB y entra con una cuenta nueva. Para revisar los datos se usa
 * viewData('ejercicios'): lo que el controlador le mandó a la vista.
 */
class EjercicioTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    // Se corre antes de cada test: carga el catálogo y crea una cuenta.
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EjercicioSeeder::class);
        $this->user = User::factory()->create();
    }

    public function test_invitados_van_al_login(): void
    {
        // Sin sesión, la ruta 'auth' manda al login.
        auth()->logout();
        $this->get('/ejercicios')->assertRedirect(route('login'));
    }

    public function test_muestra_el_catalogo_con_su_total(): void
    {
        $respuesta = $this->actingAs($this->user)->get('/ejercicios');

        // Responde bien, muestra el total del catálogo y ya no está en construcción.
        $respuesta->assertOk()
            ->assertSee(Ejercicio::count().' ejercicios')
            ->assertDontSee('En construcción');

        // Trae una sola página, del tamaño definido en config/gogym.php.
        $this->assertCount(config('gogym.max_ejercicios'), $respuesta->viewData('ejercicios'));
    }

    public function test_ordena_alfabeticamente_sin_importar_mayusculas_ni_tildes(): void
    {
        // Se busca "press" para tener nombres con mayúsculas mezcladas
        // ("Press Spoto", "Press de Banca…"), que antes salían desordenados.
        $nombres = $this->actingAs($this->user)->get('/ejercicios?q=press')
            ->viewData('ejercicios')->pluck('nombre');

        // El orden esperado: los mismos nombres ordenados por su versión normalizada.
        $esperado = $nombres->sortBy(fn ($nombre) => Ejercicio::normalizar($nombre))->values();

        $this->assertSame($esperado->all(), $nombres->all());
    }

    public function test_la_busqueda_ignora_tildes_y_mayusculas(): void
    {
        // Cuántos resultados da cada forma de escribir lo mismo.
        $totales = collect(['biceps', 'Bíceps', 'BICEPS'])->map(
            fn ($texto) => $this->actingAs($this->user)->get('/ejercicios?q='.urlencode($texto))
                ->viewData('ejercicios')->total()
        );

        // Encuentra algo, y las tres búsquedas encuentran lo mismo.
        $this->assertGreaterThan(0, $totales[0]);
        $this->assertSame([$totales[0], $totales[0], $totales[0]], $totales->all());
    }

    public function test_filtra_por_grupo_muscular(): void
    {
        $ejercicios = $this->actingAs($this->user)->get('/ejercicios?grupo=Pecho')
            ->viewData('ejercicios');

        // Hay resultados y todos son del grupo elegido.
        $this->assertGreaterThan(0, $ejercicios->total());
        $this->assertSame(['Pecho'], $ejercicios->pluck('grupo')->unique()->values()->all());
    }

    public function test_combina_busqueda_y_grupo(): void
    {
        $ejercicios = $this->actingAs($this->user)->get('/ejercicios?q=press&grupo=Pecho')
            ->viewData('ejercicios');

        // Cada resultado cumple las dos condiciones a la vez.
        foreach ($ejercicios as $ejercicio) {
            $this->assertSame('Pecho', $ejercicio->grupo);
            $this->assertStringContainsString('press', $ejercicio->nombre_orden);
        }
    }

    public function test_sin_resultados_muestra_el_estado_vacio(): void
    {
        $this->actingAs($this->user)->get('/ejercicios?q=zzzz')
            ->assertOk()
            ->assertSee('No encontramos ejercicios');
    }

    public function test_la_paginacion_usa_max_ejercicios_y_mantiene_el_filtro(): void
    {
        // Se cambia el ajuste solo para este test.
        config(['gogym.max_ejercicios' => 5]);

        $ejercicios = $this->actingAs($this->user)->get('/ejercicios?grupo=Pecho')
            ->viewData('ejercicios');

        // La página trae 5, y el enlace a la siguiente conserva el grupo.
        $this->assertCount(5, $ejercicios);
        $this->assertStringContainsString('grupo=Pecho', $ejercicios->nextPageUrl());
    }
}
