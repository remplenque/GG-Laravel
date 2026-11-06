<?php

namespace App\Http\Controllers;

use App\Models\Rutina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

    /** Vista 03 · Generar rutina con IA. Pendiente. */
    public function generar(): View
    {
        return view('rutinas.generar');
    }
}
