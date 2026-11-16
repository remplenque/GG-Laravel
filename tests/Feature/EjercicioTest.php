<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\Entrenamiento;
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

    // ===================== Vista 06 · Detalle de ejercicio =====================

    public function test_el_detalle_muestra_la_ficha_y_las_instrucciones(): void
    {
        $ejercicio = Ejercicio::findOrFail('bench-press');

        $this->actingAs($this->user)->get(route('ejercicios.show', $ejercicio))
            ->assertOk()
            ->assertSee($ejercicio->nombre)
            ->assertSee($ejercicio->instrucciones[0])
            ->assertDontSee('En construcción');
    }

    public function test_sin_historial_las_marcas_quedan_vacias(): void
    {
        $respuesta = $this->actingAs($this->user)->get(route('ejercicios.show', 'bench-press'));

        $this->assertNull($respuesta->viewData('ultimaVez'));
        $this->assertNull($respuesta->viewData('mejorMarca'));
    }

    public function test_ultima_vez_es_la_mejor_serie_de_la_sesion_mas_reciente(): void
    {
        // Hace 7 días levantó más (50 kg), pero la sesión más reciente es la de ayer.
        $this->sesion($this->user, now()->subDays(7), [[50, 5]]);
        $this->sesion($this->user, now()->subDay(), [[40, 10], [45, 8]]);

        $ultimaVez = $this->actingAs($this->user)->get(route('ejercicios.show', 'bench-press'))
            ->viewData('ultimaVez');

        $this->assertSame(45.0, $ultimaVez->kg);
        $this->assertSame(8, $ultimaVez->reps);
    }

    public function test_mejor_marca_es_la_serie_con_mas_kg_de_todas(): void
    {
        $this->sesion($this->user, now()->subDays(7), [[50, 5], [50, 6]]);
        $this->sesion($this->user, now()->subDay(), [[45, 8]]);

        $mejor = $this->actingAs($this->user)->get(route('ejercicios.show', 'bench-press'))
            ->viewData('mejorMarca');

        // Empate en 50 kg: gana la de más repeticiones.
        $this->assertSame(50.0, $mejor->kg);
        $this->assertSame(6, $mejor->reps);
    }

    public function test_no_muestra_marcas_de_otra_cuenta(): void
    {
        // Otra persona levantó 100 kg; esta cuenta, 40.
        $this->sesion(User::factory()->create(), now()->subDay(), [[100, 5]]);
        $this->sesion($this->user, now()->subDays(3), [[40, 10]]);

        $respuesta = $this->actingAs($this->user)->get(route('ejercicios.show', 'bench-press'));

        $this->assertSame(40.0, $respuesta->viewData('ultimaVez')->kg);
        $this->assertSame(40.0, $respuesta->viewData('mejorMarca')->kg);
    }

    public function test_el_detalle_enlaza_al_coach_con_la_pregunta(): void
    {
        $ejercicio = Ejercicio::findOrFail('bench-press');
        $pregunta = '¿Cuál es el error más común en '.mb_strtolower($ejercicio->nombre).'?';

        // e() escapa los & del enlace igual que lo hace Blade en el href.
        $this->actingAs($this->user)->get(route('ejercicios.show', $ejercicio))
            ->assertSee(e(route('coach', ['pregunta' => $pregunta])), escape: false);
    }

    /**
     * Crea una sesión de $user en $fecha con series de press de banca.
     *
     * @param  list<array{0: float|int, 1: int}>  $series  [kg, reps] de cada serie
     */
    private function sesion(User $user, $fecha, array $series): Entrenamiento
    {
        $entrenamiento = $user->entrenamientos()->create([
            'nombre' => 'Push',
            'inicio' => $fecha,
            'fin' => $fecha->copy()->addHour(),
        ]);

        foreach ($series as $i => [$kg, $reps]) {
            $entrenamiento->series()->create([
                'ejercicio_id' => 'bench-press',
                'numero' => $i + 1,
                'kg' => $kg,
                'reps' => $reps,
            ]);
        }

        return $entrenamiento;
    }
}
