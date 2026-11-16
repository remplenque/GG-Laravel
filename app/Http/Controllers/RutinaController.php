<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerarRutinaRequest;
use App\Models\Ejercicio;
use App\Models\Rutina;
use App\Support\PropuestaRutina;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pestaña "Rutinas": la lista de rutinas de la cuenta y el detalle de cada una.
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
            $propuesta = $propuestas->validar(
                $propuestas->generar($preferencias, $permitidos, $variante),
                $permitidos,
                (int) $preferencias['dias'],
            );
        } catch (ValidationException) {
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
}
