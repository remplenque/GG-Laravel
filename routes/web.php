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

    // Detalle de una rutina. {rutina} es el id y, como el método recibe
    // "Rutina $rutina", Laravel busca la fila solo (route model binding) y
    // responde 404 si no existe. whereNumber('rutina') acepta solo números:
    // así /rutinas/generar nunca se confunde con el detalle de una rutina,
    // sin importar en qué orden queden declaradas las rutas.
    Route::get('rutinas/{rutina}', [RutinaController::class, 'show'])
        ->whereNumber('rutina')
        ->name('rutinas.show');

    // Registrar entrenamiento (vista 05). Inicio y Rutinas enlazan aquí con
    // ?rutina={id} para abrir el registro con esa rutina precargada.
    Route::get('entrenamientos', [EntrenamientoController::class, 'index'])->name('entrenamientos.index');
    Route::get('entrenamientos/registrar', [EntrenamientoController::class, 'create'])->name('entrenamientos.create');

    // Vista 02 · Coach IA (chat).
    Route::get('coach', CoachController::class)->name('coach');

    // Biblioteca de ejercicios y su detalle (vista 06). Aquí {ejercicio} es
    // un texto (p. ej. "bench-press"), por eso no lleva whereNumber.
    Route::get('ejercicios', [EjercicioController::class, 'index'])->name('ejercicios.index');
    Route::get('ejercicios/{ejercicio}', [EjercicioController::class, 'show'])->name('ejercicios.show');

    Route::get('perfil', PerfilController::class)->name('perfil');
});

// Login, registro, recuperar contraseña y cerrar sesión (Laravel Breeze).
require __DIR__.'/auth.php';
