<?php

namespace App\Http\Controllers;

use App\Models\Ejercicio;
use App\Models\EntrenamientoSerie;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Biblioteca de ejercicios: el catálogo RepDB (601 ejercicios) y el detalle
 * de cada uno. El catálogo vive en la tabla `ejercicios` (lo carga
 * EjercicioSeeder desde database/data/ejercicios.json), así que no se llama a
 * ninguna API externa.
 */
class EjercicioController extends Controller
{
    /**
     * GET /ejercicios (ejercicios.index): la lista del catálogo, con buscador
     * y filtro por grupo muscular. Los filtros llegan en la URL, por ejemplo
     * /ejercicios?q=press&grupo=Pecho.
     */
    public function index(Request $request): View
    {
        // Empieza una consulta sobre la tabla `ejercicios` (aún no va a la base de datos).
        $ejercicios = Ejercicio::query()
            // Si viene ?q=, busca ese texto en cualquier parte del nombre:
            // "press" encuentra "Press de banca" y "Press militar".
            // when() aplica la condición SOLO si el valor viene con algo.
            // Busca en nombre_orden (sin tildes ni mayúsculas) y normaliza también
            // lo escrito, así "biceps", "Bíceps" y "BICEPS" encuentran lo mismo.
            ->when($request->query('q'), fn ($consulta, $texto) => $consulta->where('nombre_orden', 'like', '%'.Ejercicio::normalizar($texto).'%'))
            // Si viene ?grupo=, deja solo los de ese grupo muscular (Pecho, Espalda…).
            // Osea que si el usuario hace click en un chip de filtro, se queda solo con ese grupo.
            ->when($request->query('grupo'), fn ($consulta, $grupo) => $consulta->where('grupo', $grupo))
            // Ordena de la A a la Z sin importar mayúsculas ni tildes: usa
            // nombre_orden, que es el nombre en minúsculas y sin tildes.
            ->orderBy('nombre_orden')
            // Trae solo una página en vez de los 601; Laravel lee solo el ?page= de la URL.
            // Cuántos por página se define en config/gogym.php → 'max_ejercicios'.
            ->paginate(config('gogym.max_ejercicios'))
            // Mantiene q y grupo en los enlaces de las páginas, para no perder el filtro.
            ->withQueryString();

        // Abre resources/views/ejercicios/index.blade.php. Cada llave llega
        // a la vista como variable: 'ejercicios' se lee como $ejercicios, etc.
        return view('ejercicios.index', [
            // La página de ejercicios ya filtrada.
            'ejercicios' => $ejercicios,
            // Los grupos que existen en el catálogo, sin repetir (distinct),
            // para dibujar los chips del filtro.
            // pluck sirve para traer solo la columna 'grupo' de cada fila, en vez de todo el registro.
            'grupos' => Ejercicio::query()->distinct()->orderBy('grupo')->pluck('grupo'),
            // Lo que escribió el usuario, para dejarlo en el buscador.
            'q' => $request->query('q'),
            // El grupo elegido, para marcar su chip como activo.
            'grupo' => $request->query('grupo'),
        ]);
    }

    /**
     * GET /ejercicios/{ejercicio} (ejercicios.show): vista 06 · Detalle de ejercicio.
     *
     * Además de la ficha del catálogo, muestra dos marcas del usuario con
     * sesión en este ejercicio. Las dos son una EntrenamientoSerie (o null si
     * nunca lo ha hecho), así la vista lee ->kg y ->reps de la misma forma:
     *   - Última vez: la mejor serie de su sesión más reciente con este ejercicio.
     *   - Mejor marca: su serie con más kg de todas (si empatan, la de más reps).
     */
    public function show(Request $request, Ejercicio $ejercicio): View
    {
        $user = $request->user();

        // La sesión más reciente en que hizo este ejercicio. entrenamientos()
        // ya viene de la más nueva a la más antigua (latest('inicio') en User);
        // whereHas deja solo las que tienen alguna serie de este ejercicio.
        // Queda en null si nunca lo ha hecho.
        $ultimaSesion = $user->entrenamientos()
            ->whereHas('series', fn ($consulta) => $consulta->where('ejercicio_id', $ejercicio->id))
            ->first();

        // Su mejor serie de este ejercicio en esa sesión. reorder() borra el
        // orden por 'orden' y 'numero' que trae la relación series(), para
        // ordenar por kg y, si empatan, por reps.
        if ($ultimaSesion) {
            $ultimaVez = $ultimaSesion->series()
                ->where('ejercicio_id', $ejercicio->id)
                ->reorder()
                ->orderByDesc('kg')
                ->orderByDesc('reps')
                ->first();
        } else {
            $ultimaVez = null;
        }

        // Su mejor marca: la serie con más kg entre TODAS sus sesiones. Las
        // series no tienen user_id, por eso whereHas('entrenamiento') filtra
        // por el dueño de la sesión; sin él aparecerían las marcas de otras
        // cuentas (lo revisa test_no_muestra_marcas_de_otra_cuenta).
        $mejorMarca = EntrenamientoSerie::query()
            ->where('ejercicio_id', $ejercicio->id)
            ->whereHas('entrenamiento', fn ($consulta) => $consulta->where('user_id', $user->id))
            ->orderByDesc('kg')
            ->orderByDesc('reps')
            ->first();

        return view('ejercicios.show', [
            'ejercicio' => $ejercicio,
            'ultimaVez' => $ultimaVez,
            'mejorMarca' => $mejorMarca,
            // Para el selector "Agregar a una rutina": solo id y nombre.
            'rutinas' => $user->rutinas()->get(['id', 'nombre']),
        ]);
    }
}
