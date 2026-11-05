<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un entrenamiento (sesión) del usuario.
        Schema::create('entrenamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Rutina de la que salió; null en entrenamientos libres.
            $table->foreignId('rutina_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nombre');
            $table->dateTime('inicio');
            $table->dateTime('fin')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'inicio']);
        });

        // Cada serie hecha dentro de un entrenamiento.
        Schema::create('entrenamiento_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrenamiento_id')->constrained()->cascadeOnDelete();
            $table->string('ejercicio_id');
            $table->foreign('ejercicio_id')->references('id')->on('ejercicios');
            // Posición del ejercicio dentro del entrenamiento y de la serie dentro del ejercicio.
            $table->unsignedSmallInteger('orden')->default(0);
            $table->unsignedSmallInteger('numero')->default(1);
            // null = el usuario no lo rellenó (p. ej. ejercicios sin carga).
            $table->decimal('kg', 6, 2)->nullable();
            $table->unsignedSmallInteger('reps')->nullable();
            // Esfuerzo percibido: 1 = muy fácil, 10 = al fallo.
            $table->unsignedTinyInteger('rpe')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrenamiento_series');
        Schema::dropIfExists('entrenamientos');
    }
};
