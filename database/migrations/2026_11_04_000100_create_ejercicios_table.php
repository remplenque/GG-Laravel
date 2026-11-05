<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de ejercicios (RepDB, traducido al español). Es igual para todos
     * los usuarios y se carga con EjercicioSeeder. El id es el slug estable
     * ("barbell-bench-press") que usan las rutinas y los entrenamientos.
     */
    public function up(): void
    {
        Schema::create('ejercicios', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('nombre');
            $table->text('descripcion')->default('');
            $table->string('grupo')->index();
            $table->string('categoria');
            $table->string('nivel');
            $table->string('equipo');
            $table->boolean('peso_corporal')->default(false);
            $table->json('musculos');
            $table->json('musculos_secundarios');
            $table->decimal('met', 4, 1);
            // Nombres de archivo; la URL se arma con config('gogym.imagenes_url').
            $table->string('imagen')->nullable();
            $table->string('imagen_final')->nullable();
            $table->json('instrucciones');
            $table->json('consejos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejercicios');
    }
};
