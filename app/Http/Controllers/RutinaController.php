<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarRutinaRequest;
use App\Http\Requests\GenerarRutinaRequest;
use App\Models\Ejercicio;
use App\Models\Rutina;
use App\Support\PropuestaRutina;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Pestaña "Rutinas": la lista de rutinas de la cuenta, el detalle de cada una,
 * el flujo con IA (formulario 03 → propuesta 04 → guardar) y editar/borrar.
 *
 * Como en el resto de la app, el controlador consigue los datos (con los
 * modelos de Eloquent) y elige qué vista mostrar; el HTML vive en
 * resources/views/rutinas/.
 */
class RutinaController extends Controller
{
    /** Llave de la sesión donde vive la propuesta de la IA hasta guardarla. */
    private const SESION = 'rutina_ia';

    /**
     * GET /rutinas (rutinas.index): las rutinas de la cuenta. La que toca
     * según la rotación va destacada y cada una muestra su lugar en la rotación.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // with('ejercicios.ejercicio') trae de una vez los ejercicios de cada
        // rutina y la ficha de cada ejercicio (eager loading), en vez de hacer
        // una consulta por rutina dentro de la vista.
        $rutinas = $user->rutinas()->with('ejercicios.ejercicio')->get();

        // La rutina que sigue a la última entrenada. Para eso solo hace falta
        // saber qué rutina usó cada entrenamiento, así que se piden 2 columnas.
        $siguiente = Rutina::siguiente($rutinas, $user->entrenamientos()->get(['id', 'rutina_id']));

        // Lugar de cada rutina en la rotación, como [id de la rutina => 1, 2, 3…].
        // Solo cuentan las que tienen ejercicios (una vacía no se puede empezar).
        $orden = $rutinas
            ->filter(fn (Rutina $r) => $r->ejercicios->isNotEmpty())
            ->values()
            ->mapWithKeys(fn (Rutina $r, int $i) => [$r->id => $i + 1]);

        return view('rutinas.index', [
            'rutinas' => $rutinas,
            'siguiente' => $siguiente,
            'orden' => $orden,
        ]);
    }

    /**
     * GET /rutinas/{rutina} (rutinas.show): el detalle de una rutina.
     *
     * "Rutina $rutina" activa el route model binding: Laravel ve el {rutina}
     * de la ruta, busca esa fila y entrega el objeto completo (o responde 404
     * si el id no existe), sin que haya que escribir Rutina::find().
     */
    public function show(Rutina $rutina): View
    {
        // Gate::authorize revisa RutinaPolicy@view: solo el dueño puede ver
        // su rutina. Si es de otra cuenta, Laravel responde 403 y corta aquí.
        Gate::authorize('view', $rutina);

        return view('rutinas.show', [
            // load() trae los ejercicios (en su orden) y la ficha de cada uno.
            'rutina' => $rutina->load('ejercicios.ejercicio'),
        ]);
    }

    /**
     * GET /rutinas/generar (rutinas.generar): vista 03 · formulario "Arma tu rutina".
     *
     * Le pasa a la vista las opciones de config/gogym.php y la cuenta, para
     * precargar el objetivo, el nivel y los días que ya tiene en su perfil.
     */
    public function generar(Request $request): View
    {
        return view('rutinas.generar', [
            'opciones' => config('gogym.rutina_ia'),
            'user' => $request->user(),
        ]);
    }

    /**
     * POST /rutinas/generar (rutinas.proponer): recibe el formulario y arma
     * la propuesta.
     *
     * Recibir GenerarRutinaRequest en vez de Request hace que Laravel valide
     * ANTES de entrar aquí: si llega a esta línea, los datos ya son válidos y
     * $request->validated() trae solo los campos revisados.
     *
     * PropuestaRutina $propuestas lo crea Laravel solo (inyección de
     * dependencias): basta con pedirlo como parámetro.
     */
    public function proponer(GenerarRutinaRequest $request, PropuestaRutina $propuestas): RedirectResponse
    {
        return $this->armarPropuesta($request->validated(), $propuestas);
    }

    /**
     * GET /rutinas/propuesta (rutinas.propuesta): vista 04 · Rutina generada.
     *
     * Lee la propuesta de la sesión. Si no hay (se entró directo por la URL o
     * ya se guardó), vuelve al formulario.
     */
    public function propuesta(Request $request): View|RedirectResponse
    {
        $datos = $request->session()->get(self::SESION);
        if ($datos === null) {
            return redirect()->route('rutinas.generar');
        }

        // La propuesta solo trae ids: se buscan los ejercicios de una vez
        // (findMany) y keyBy('id') deja la colección como [id => Ejercicio],
        // para que la vista los encuentre con $ejercicios[$id].
        $ids = collect($datos['propuesta']['dias'])->pluck('ejercicios.*.ejercicio_id')->flatten();

        return view('rutinas.propuesta', [
            'propuesta' => $datos['propuesta'],
            'preferencias' => $datos['preferencias'],
            'ejercicios' => Ejercicio::findMany($ids)->keyBy('id'),
        ]);
    }

    /**
     * POST /rutinas/propuesta/regenerar (rutinas.regenerar): otra propuesta
     * con las mismas preferencias, sin volver a llenar el formulario.
     */
    public function regenerar(Request $request, PropuestaRutina $propuestas): RedirectResponse
    {
        $datos = $request->session()->get(self::SESION);
        if ($datos === null) {
            return redirect()->route('rutinas.generar');
        }

        return $this->armarPropuesta($datos['preferencias'], $propuestas, $datos['variante'] + 1);
    }

    /**
     * Arma, valida y guarda la propuesta en la sesión, y redirige a la vista 04.
     *
     * Post/Redirect/Get: el POST nunca muestra una página, siempre redirige a
     * un GET. Así recargar la vista 04 no vuelve a gastar una llamada a la IA,
     * y no hacen falta tablas de "borradores": la propuesta vive en la sesión
     * hasta que se guarda o se descarta.
     */
    private function armarPropuesta(array $preferencias, PropuestaRutina $propuestas, int $variante = 0): RedirectResponse
    {
        $permitidos = $propuestas->permitidos($preferencias);

        try {
            $propuesta = null;
            for ($intento = 0; $intento < 2; $intento++) {
                try {
                    $propuesta = $propuestas->validar(
                        $propuestas->generar($preferencias, $permitidos, $variante + $intento),
                        $permitidos,
                        (int) $preferencias['dias'],
                    );
                    break;
                } catch (ValidationException $error) {
                    if ($intento === 1) {
                        throw $error;
                    }
                }
            }
        } catch (Throwable $error) {
            report($error);

            // La propuesta no tiene la forma acordada (con la IA: un ejercicio
            // inventado, un día de menos…). Se avisa en vez de mostrar algo roto.
            return redirect()->route('rutinas.generar')
                ->withInput($preferencias)
                ->with('error', 'El coach no pudo armar una rutina con esas opciones. Prueba con más equipamiento o vuelve a intentarlo.');
        }

        session()->put(self::SESION, [
            'preferencias' => $preferencias,
            'propuesta' => $propuesta,
            'variante' => $variante,
        ]);

        return redirect()->route('rutinas.propuesta');
    }

    /**
     * POST /rutinas/propuesta (rutinas.guardar): "Guardar en mis rutinas".
     *
     * La propuesta se lee de la SESIÓN, nunca del formulario: así nadie puede
     * mandar a mano ejercicios que no pasaron por validar(). Del formulario
     * solo llega la casilla "reemplazar".
     *
     * Cada día de la propuesta se guarda como una Rutina ("Torso A",
     * "Pierna A"…) y cada ejercicio como un RutinaEjercicio. Así entran solas
     * a la rotación de Rutina::siguiente(): Inicio, Perfil y Registrar las
     * toman sin cambiar nada.
     */
    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->session()->get(self::SESION);
        if ($datos === null) {
            return redirect()->route('rutinas.generar');
        }

        $user = $request->user();
        $preferencias = $datos['preferencias'];
        $dias = $datos['propuesta']['dias'];

        // DB::transaction: o se guarda todo, o nada. Si algo falla a mitad de
        // camino (p. ej. en el tercer día), se deshacen también los dos
        // primeros y no queda una rutina a medias.
        DB::transaction(function () use ($request, $user, $preferencias, $dias) {
            // "Reemplazar mis rutinas actuales": borra las anteriores. El
            // historial no se pierde: entrenamientos.rutina_id es
            // nullOnDelete, así que esas sesiones quedan como "libres".
            if ($request->boolean('reemplazar')) {
                $user->rutinas()->delete();
            }

            foreach ($dias as $dia) {
                // create() desde la relación llena user_id solo.
                $rutina = $user->rutinas()->create([
                    'nombre' => $dia['nombre'],
                    'categoria' => 'Fuerza',
                    'nivel' => $preferencias['nivel'],
                    'minutos' => $preferencias['minutos'],
                ]);

                // createMany() inserta todos los ejercicios del día de una
                // vez, con rutina_id puesto. El RPE va en "carga" ("RPE 7"):
                // RutinaEjercicio::resumen ya lo muestra como "4 × 8 · RPE 7".
                $rutina->ejercicios()->createMany(collect($dia['ejercicios'])->map(fn (array $item, int $orden) => [
                    'ejercicio_id' => $item['ejercicio_id'],
                    'orden' => $orden,
                    'series' => $item['series'],
                    'repeticiones' => $item['repeticiones'],
                    'carga' => 'RPE '.$item['rpe'],
                    'descanso_seg' => $item['descanso_seg'],
                    'nota' => $item['nota'] ?? null,
                ])->all());
            }
        });

        // Ya se guardó: se borra de la sesión para no guardarla dos veces.
        $request->session()->forget(self::SESION);

        $cantidad = count($dias);

        return redirect()->route('rutinas.index')
            ->with('estado', "Guardamos {$cantidad} ".($cantidad === 1 ? 'rutina' : 'rutinas').' en tu rotación.');
    }

    /**
     * GET /rutinas/{rutina}/editar (rutinas.editar): formulario para cambiar
     * el nombre, las series, repeticiones y descanso, o quitar ejercicios.
     */
    public function editar(Rutina $rutina): View
    {
        Gate::authorize('update', $rutina);

        return view('rutinas.editar', [
            'rutina' => $rutina->load('ejercicios.ejercicio'),
        ]);
    }

    /**
     * PUT /rutinas/{rutina} (rutinas.actualizar): guarda la edición.
     *
     * ActualizarRutinaRequest ya revisó que sea el dueño y que los números
     * estén en rango. Aquí se recorren los ejercicios de ESTA rutina y se
     * busca lo que llegó para cada uno por su id: si mandaran el id de un
     * ejercicio de otra rutina, simplemente no se encuentra y se ignora.
     */
    public function actualizar(ActualizarRutinaRequest $request, Rutina $rutina): RedirectResponse
    {
        $datos = $request->validated();

        DB::transaction(function () use ($rutina, $datos) {
            $rutina->update(['nombre' => $datos['nombre']]);

            foreach ($rutina->ejercicios as $item) {
                $cambios = $datos['ejercicios'][$item->id] ?? null;
                if ($cambios === null) {
                    continue;
                }

                if (! empty($cambios['quitar'])) {
                    $item->delete();
                } else {
                    $item->update([
                        'series' => $cambios['series'],
                        'repeticiones' => $cambios['repeticiones'],
                        'descanso_seg' => $cambios['descanso_seg'],
                    ]);
                }
            }
        });

        return redirect()->route('rutinas.show', $rutina)->with('estado', 'Guardamos los cambios de la rutina.');
    }

    /**
     * DELETE /rutinas/{rutina} (rutinas.eliminar): borra la rutina y sus
     * ejercicios (cascadeOnDelete en rutina_ejercicios). Los entrenamientos
     * que salieron de ella se conservan como sesiones libres (nullOnDelete).
     */
    public function eliminar(Rutina $rutina): RedirectResponse
    {
        Gate::authorize('delete', $rutina);

        $rutina->delete();

        return redirect()->route('rutinas.index')->with('estado', "Borramos {$rutina->nombre}.");
    }

    /**
     * POST /ejercicios/{ejercicio}/agregar (ejercicios.agregar): "Agregar a
     * una rutina" desde el detalle del ejercicio (vista 06).
     *
     * La regla exists con where(user_id) hace que solo se acepten rutinas de
     * ESTA cuenta: el id de una rutina ajena da error de validación, igual
     * que uno que no existe. Entra al final de la rutina con valores
     * generales (3 × 10, descanso 90 s), que después se ajustan en Editar.
     */
    public function agregarEjercicio(Request $request, Ejercicio $ejercicio): RedirectResponse
    {
        $datos = $request->validate([
            'rutina_id' => ['required', Rule::exists('rutinas', 'id')->where('user_id', $request->user()->id)],
        ], [], ['rutina_id' => 'rutina']);

        $rutina = $request->user()->rutinas()->findOrFail($datos['rutina_id']);

        $rutina->ejercicios()->create([
            'ejercicio_id' => $ejercicio->id,
            // max('orden') + 1: queda después del último. Si la rutina está
            // vacía, max() da null y (null ?? -1) + 1 = 0.
            'orden' => ($rutina->ejercicios()->max('orden') ?? -1) + 1,
            'series' => 3,
            'repeticiones' => 10,
            'descanso_seg' => 90,
        ]);

        return redirect()->route('rutinas.show', $rutina)
            ->with('estado', "Agregamos {$ejercicio->nombre} a {$rutina->nombre}.");
    }
}
