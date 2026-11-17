<?php

use App\Models\Ejercicio;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega nombre_orden: el nombre del ejercicio en minúsculas y sin tildes.
     *
     * SQLite ordena letra por letra según su código, así que las mayúsculas
     * van antes que las minúsculas ("Press Spoto" antes que "Press de banca")
     * y las letras con tilde quedan al final ("Élevación" después de "Zancada").
     * Ordenando por esta columna la biblioteca sale en orden alfabético real.
     */
    public function up(): void
    {
        // Crea la columna. index() hace que ordenar por ella sea rápido.
        Schema::table('ejercicios', function (Blueprint $table) {
            $table->string('nombre_orden')->default('')->index();
        });

        // Llena la columna para los ejercicios que ya estaban cargados, así no
        // hay que volver a correr el seeder. Los nuevos los llena EjercicioSeeder.
        foreach (Ejercicio::all(['id', 'nombre']) as $ejercicio) {
            $ejercicio->update(['nombre_orden' => Ejercicio::normalizar($ejercicio->nombre)]);
        }
    }

    /** Deshace la migración: borra la columna. */
    public function down(): void
    {
        Schema::table('ejercicios', function (Blueprint $table) {
            $table->dropColumn('nombre_orden');
        });
    }
};
