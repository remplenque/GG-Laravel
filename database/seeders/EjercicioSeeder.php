<?php

namespace Database\Seeders;

use App\Models\Ejercicio;
use Illuminate\Database\Seeder;

/**
 * Carga el catálogo de ejercicios (RepDB, traducido al español) desde
 * database/data/ejercicios.json. Idempotente: se puede correr de nuevo.
 */
class EjercicioSeeder extends Seeder
{
    private const JSON = ['musculos', 'musculos_secundarios', 'instrucciones', 'consejos'];

    public function run(): void
    {
        $filas = json_decode(file_get_contents(database_path('data/ejercicios.json')), true, flags: JSON_THROW_ON_ERROR);

        $filas = array_map(function (array $fila) {
            foreach (self::JSON as $columna) {
                $fila[$columna] = json_encode($fila[$columna], JSON_UNESCAPED_UNICODE);
            }

            return $fila;
        }, $filas);

        foreach (array_chunk($filas, 100) as $lote) {
            Ejercicio::upsert($lote, ['id']);
        }
    }
}
