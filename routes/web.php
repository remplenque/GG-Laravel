<?php

/*
|--------------------------------------------------------------------------
| Rutas web de Go Gym
|--------------------------------------------------------------------------
|
| Este archivo es el "mapa" del sitio: cada línea conecta una dirección (URL)
| y un verbo HTTP (GET, POST…) con el método del controlador que responde.
| A cada ruta se le pone un nombre con ->name(): así, en las vistas se escribe
| route('rutinas.index') en vez de la URL a mano, y si mañana cambia la
| dirección basta con cambiar UNA línea aquí.
|
| Para ver el mapa completo: php artisan route:list
|
*/

use App\Http\Controllers\CoachController;
use App\Http\Controllers\EjercicioController;
use App\Http\Controllers\EntrenamientoController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\RutinaController;
use Illuminate\Support\Facades\Route;

// Todo lo de este grupo exige sesión iniciada: el middleware 'auth' manda a
// /login a quien entre sin cuenta. Las rutas de login y registro viven aparte,
// en routes/auth.php (las creó Laravel Breeze).
Route::middleware('auth')->group(function () {

    // Vista 01 · Inicio (dashboard). InicioController tiene un solo método
    // (__invoke), por eso se pasa la clase sola y no [Clase, 'método'].
    Route::get('/', InicioController::class)->name('inicio');

    // Rutinas de la cuenta: la lista y el formulario para generar una con IA.
    Route::get('rutinas', [RutinaController::class, 'index'])->name('rutinas.index');
    Route::get('rutinas/generar', [RutinaController::class, 'generar'])->name('rutinas.generar');

    // El formulario "Arma tu rutina" se envía aquí (POST: manda datos). Cada
    // envío va a gastar una llamada a la IA, así que throttle:5,1 deja máximo
    // 5 por minuto por usuario; el sexto recibe un 429 "Demasiadas solicitudes".
    // Cuando Ian defina el limitador 'ia', se cambia por throttle:ia.
    Route::post('rutinas/generar', [RutinaController::class, 'proponer'])
        ->middleware('throttle:ia-rutinas')
        ->name('rutinas.proponer');

    // Vista 04 · Rutina generada: la propuesta que quedó en la sesión, y el
    // botón Regenerar (otra llamada a la IA, por eso también con throttle).
    // No chocan con rutinas/{rutina} porque ese exige un número (whereNumber).
    Route::get('rutinas/propuesta', [RutinaController::class, 'propuesta'])->name('rutinas.propuesta');
    Route::post('rutinas/propuesta/regenerar', [RutinaController::class, 'regenerar'])
        ->middleware('throttle:ia-rutinas')
        ->name('rutinas.regenerar');

    // "Guardar en mis rutinas": pasa la propuesta de la sesión a la base de datos.
    Route::post('rutinas/propuesta', [RutinaController::class, 'guardar'])->name('rutinas.guardar');

    // Detalle de una rutina. {rutina} es el id y, como el método recibe
    // "Rutina $rutina", Laravel busca la fila solo (route model binding) y
    // responde 404 si no existe. whereNumber('rutina') acepta solo números:
    // así /rutinas/generar nunca se confunde con el detalle de una rutina,
    // sin importar en qué orden queden declaradas las rutas.
    Route::get('rutinas/{rutina}', [RutinaController::class, 'show'])
        ->whereNumber('rutina')
        ->name('rutinas.show');

    // Editar y borrar una rutina. Los formularios HTML solo mandan GET o
    // POST: para PUT y DELETE se agrega @method('PUT') o @method('DELETE')
    // dentro del <form> y Laravel lo trata como ese verbo.
    Route::get('rutinas/{rutina}/editar', [RutinaController::class, 'editar'])
        ->whereNumber('rutina')
        ->name('rutinas.editar');
    Route::put('rutinas/{rutina}', [RutinaController::class, 'actualizar'])
        ->whereNumber('rutina')
        ->name('rutinas.actualizar');
    Route::delete('rutinas/{rutina}', [RutinaController::class, 'eliminar'])
        ->whereNumber('rutina')
        ->name('rutinas.eliminar');

    // Registrar entrenamiento (vista 05). Inicio y Rutinas enlazan aquí con
    // ?rutina={id} para abrir el registro con esa rutina precargada.
    Route::get('entrenamientos', [EntrenamientoController::class, 'index'])->name('entrenamientos.index');
    Route::get('entrenamientos/registrar', [EntrenamientoController::class, 'create'])->name('entrenamientos.create');
    Route::post('entrenamientos', [EntrenamientoController::class, 'store'])->name('entrenamientos.store');
    Route::get('entrenamientos/{entrenamiento}/editar', [EntrenamientoController::class, 'edit'])
        ->whereNumber('entrenamiento')
        ->name('entrenamientos.edit');
    Route::put('entrenamientos/{entrenamiento}', [EntrenamientoController::class, 'update'])
        ->whereNumber('entrenamiento')
        ->name('entrenamientos.update');
    Route::delete('entrenamientos/{entrenamiento}', [EntrenamientoController::class, 'destroy'])
        ->whereNumber('entrenamiento')
        ->name('entrenamientos.destroy');

    // Vista 02 · Coach IA (chat): ver la conversación, enviar una pregunta y
    // empezar una nueva (borra la actual). Cada pregunta va a gastar una
    // llamada a la IA: throttle:10,1 deja máximo 10 por minuto por usuario.
    // Cuando Ian defina el limitador 'ia', se cambia por throttle:ia.
    Route::get('coach', [CoachController::class, 'index'])->name('coach');
    Route::post('coach', [CoachController::class, 'preguntar'])
        ->middleware('throttle:ia')
        ->name('coach.preguntar');
    Route::delete('coach', [CoachController::class, 'nueva'])->name('coach.nueva');

    // Biblioteca de ejercicios y su detalle (vista 06). Aquí {ejercicio} es
    // un texto (p. ej. "bench-press"), por eso no lleva whereNumber.
    Route::get('ejercicios', [EjercicioController::class, 'index'])->name('ejercicios.index');

    // "Explorar en wger" (API externa). Va ANTES de ejercicios/{ejercicio}:
    // como ese acepta cualquier texto, si estuviera primero tomaría "wger"
    // como el id de un ejercicio y respondería 404.
    Route::get('ejercicios/wger', [EjercicioController::class, 'wger'])->name('ejercicios.wger');

    Route::get('ejercicios/{ejercicio}', [EjercicioController::class, 'show'])->name('ejercicios.show');

    // "Agregar a una rutina" desde el detalle del ejercicio: la rutina elegida
    // llega en el formulario (rutina_id), por eso no va en la URL.
    Route::post('ejercicios/{ejercicio}/agregar', [RutinaController::class, 'agregarEjercicio'])
        ->name('ejercicios.agregar');

    Route::get('perfil', PerfilController::class)->name('perfil');

    // Editar los datos de entrenamiento (objetivo, nivel, peso, días y
    // horario) y borrar la cuenta con todos sus datos (Ley 21.719).
    Route::get('perfil/editar', [PerfilController::class, 'editar'])->name('perfil.editar');
    Route::put('perfil', [PerfilController::class, 'actualizar'])->name('perfil.actualizar');
    Route::delete('perfil', [PerfilController::class, 'eliminar'])->name('perfil.eliminar');
});

// Login, registro, recuperar contraseña y cerrar sesión (Laravel Breeze).
require __DIR__.'/auth.php';
