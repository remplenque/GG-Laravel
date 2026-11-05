<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rutinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('categoria')->default('');
            $table->string('nivel')->default('');
            $table->unsignedSmallInteger('minutos')->default(0);
            $table->timestamps();
        });

        // Un ejercicio planificado dentro de una rutina.
        Schema::create('rutina_ejercicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rutina_id')->constrained()->cascadeOnDelete();
            $table->string('ejercicio_id');
            $table->foreign('ejercicio_id')->references('id')->on('ejercicios');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->unsignedSmallInteger('series');
            // 0 = el ejercicio va por tiempo (la carga dice cuánto, p. ej. "60 s").
            $table->unsignedSmallInteger('repeticiones')->default(0);
            // "45 kg", "Peso corporal" o "60 s".
            $table->string('carga')->default('');
            $table->unsignedSmallInteger('descanso_seg')->default(0);
            $table->text('nota')->nullable();
            $table->timestamps();

            $table->index(['rutina_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rutina_ejercicios');
        Schema::dropIfExists('rutinas');
    }
};
