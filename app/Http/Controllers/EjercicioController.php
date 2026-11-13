<?php

namespace App\Http\Controllers;

use App\Models\Ejercicio;
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

    /** Vista 06 · Detalle de ejercicio. Pendiente. */
    public function show(Ejercicio $ejercicio): View
    {
        return view('ejercicios.show', ['ejercicio' => $ejercicio]);
    }
}
