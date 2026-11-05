<?php

use App\Http\Controllers\CoachController;
use App\Http\Controllers\EjercicioController;
use App\Http\Controllers\EntrenamientoController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\RutinaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', InicioController::class)->name('inicio');

    Route::get('rutinas', [RutinaController::class, 'index'])->name('rutinas.index');
    Route::get('rutinas/generar', [RutinaController::class, 'generar'])->name('rutinas.generar');
    Route::get('rutinas/{rutina}', [RutinaController::class, 'show'])->name('rutinas.show');

    Route::get('entrenamientos', [EntrenamientoController::class, 'index'])->name('entrenamientos.index');
    Route::get('entrenamientos/registrar', [EntrenamientoController::class, 'create'])->name('entrenamientos.create');

    Route::get('coach', CoachController::class)->name('coach');

    Route::get('ejercicios', [EjercicioController::class, 'index'])->name('ejercicios.index');
    Route::get('ejercicios/{ejercicio}', [EjercicioController::class, 'show'])->name('ejercicios.show');

    Route::get('perfil', PerfilController::class)->name('perfil');
});

require __DIR__.'/auth.php';
