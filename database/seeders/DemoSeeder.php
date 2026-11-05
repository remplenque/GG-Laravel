<?php

namespace Database\Seeders;

use App\Models\Ejercicio;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Puebla la cuenta demo (config('gogym.demo')) con perfil, rutinas y dos
 * semanas de entrenamientos. Es idempotente: borra las rutinas y entrenamientos
 * de la cuenta antes de insertar. Las fechas son relativas al día en que se
 * corre, así que conviene correrlo el día de la demo para que la racha siga viva.
 */
class DemoSeeder extends Seeder
{
    /**
     * Mismas rutinas que la cuenta demo de Flutter. Cada ejercicio:
     * [id del catálogo, series, reps, kg | null, descanso en s, carga si no hay kg].
     */
    private const RUTINAS = [
        ['Push A', 'Fuerza', 'Intermedio', 55, [
            ['bench-press', 4, 8, 60, 120, null],
            ['incline-db-press', 3, 10, 22, 90, null],
            ['ohp', 3, 8, 37.5, 90, null],
            ['lateral-raise', 4, 12, 8, 60, null],
            ['tricep-pushdown', 3, 12, 25, 60, null],
        ]],
        ['Pull A', 'Fuerza', 'Intermedio', 50, [
            ['deadlift', 3, 5, 100, 150, null],
            ['lat-pulldown', 4, 10, 55, 90, null],
            ['barbell-row', 3, 8, 50, 90, null],
            ['face-pull', 3, 15, 20, 60, null],
            ['barbell-curl', 3, 10, 25, 60, null],
        ]],
        ['Legs A', 'Fuerza', 'Avanzado', 60, [
            ['squat', 5, 5, 80, 150, null],
            ['romanian-deadlift', 4, 8, 60, 120, null],
            ['leg-press', 3, 12, 140, 90, null],
            ['leg-curl', 3, 12, 35, 60, null],
            ['machine-calf-raise', 4, 15, 50, 45, null],
        ]],
        ['Core y cardio', 'Cardio', 'Principiante', 35, [
            ['jump-rope', 4, 0, null, 45, '90 s'],
            ['plank', 3, 0, null, 45, '60 s'],
            ['bicycle-crunch', 3, 20, null, 45, 'Peso corporal'],
            ['hanging-leg-raise', 3, 12, null, 60, 'Peso corporal'],
        ]],
    ];

    /**
     * Días atrás con entrenamiento: 4 seguidos, un descanso y los últimos 8 días
     * hasta ayer. Hoy queda libre para registrar uno en vivo.
     */
    private const DIAS_ATRAS = [13, 12, 11, 10, 8, 7, 6, 5, 4, 3, 2, 1];

    public function run(): void
    {
        if (Ejercicio::query()->doesntExist()) {
            $this->call(EjercicioSeeder::class);
        }

        $user = User::updateOrCreate(['email' => config('gogym.demo.email')], [
            'name' => 'Vicente',
            'apellido' => 'Rodríguez',
            'password' => config('gogym.demo.password'),
            'objetivo' => 'Ganar músculo',
            'nivel' => 'Intermedio',
            'peso_kg' => 78,
            'dias_entrenamiento' => [0, 1, 2, 3, 4, 5],
            'hora_entrenamiento' => '18:00',
        ]);
        $user->entrenamientos()->delete();
        $user->rutinas()->delete();

        $rutinas = [];
        foreach (self::RUTINAS as [$nombre, $categoria, $nivel, $minutos, $ejercicios]) {
            $rutina = $user->rutinas()->create(compact('nombre', 'categoria', 'nivel', 'minutos'));
            foreach ($ejercicios as $orden => [$ejercicio, $series, $reps, $kg, $descanso, $carga]) {
                $rutina->ejercicios()->create([
                    'ejercicio_id' => $ejercicio,
                    'orden' => $orden,
                    'series' => $series,
                    'repeticiones' => $reps,
                    'carga' => $kg === null ? $carga : rtrim(rtrim(number_format($kg, 1, '.', ''), '0'), '.').' kg',
                    'descanso_seg' => $descanso,
                ]);
            }
            $rutinas[] = $rutina;
        }

        mt_srand(2026);
        foreach (self::DIAS_ATRAS as $n => $diasAtras) {
            // Rota las rutinas y sube la carga en cada vuelta.
            $ciclo = intdiv($n, count(self::RUTINAS));
            $r = $n % count(self::RUTINAS);
            [$nombre, , , $minutos, $ejercicios] = self::RUTINAS[$r];

            $inicio = today()->subDays($diasAtras)->setTime(18, mt_rand(0, 25));
            $entrenamiento = $user->entrenamientos()->create([
                'rutina_id' => $rutinas[$r]->id,
                'nombre' => $nombre,
                'inicio' => $inicio,
                'fin' => $inicio->copy()->addMinutes($minutos + mt_rand(-6, 8)),
            ]);

            foreach ($ejercicios as $orden => [$ejercicio, $series, $reps, $kg]) {
                for ($s = 0; $s < $series; $s++) {
                    if ($kg === null) {
                        $serie = ['kg' => null, 'reps' => $reps ?: null, 'rpe' => mt_rand(6, 8)];
                    } else {
                        $paso = $kg >= 50 ? 5 : ($kg >= 20 ? 2.5 : 1);
                        $falla = $s === $series - 1 && mt_rand(0, 9) < 4;
                        $serie = [
                            'kg' => $kg + $paso * $ciclo,
                            'reps' => $reps - ($falla ? 1 : 0),
                            'rpe' => min(10, 7 + intdiv($s, 2) + mt_rand(0, 1)),
                        ];
                    }
                    $entrenamiento->series()->create([
                        'ejercicio_id' => $ejercicio,
                        'orden' => $orden,
                        'numero' => $s + 1,
                        ...$serie,
                    ]);
                }
            }
        }
    }
}
