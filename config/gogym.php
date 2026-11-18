<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Imágenes de ejercicios
    |--------------------------------------------------------------------------
    |
    | Las imágenes del catálogo (RepDB) se sirven por jsDelivr, fijadas a un
    | commit para que no cambien. La tabla `ejercicios` guarda solo el nombre
    | del archivo y la URL se arma con esta base.
    |
    */

    'imagenes_url' => env(
        'GOGYM_IMAGENES_URL',
        'https://cdn.jsdelivr.net/gh/RepDB/exercise-dataset@6d88fc6f3a398845f7f2c17f3a5f45cfc4effa2f/images/flat/'
    ),

    /*
    |--------------------------------------------------------------------------
    | Estimación de calorías
    |--------------------------------------------------------------------------
    |
    | kcal = MET × peso × horas. Se usa este MET para ejercicios sin dato y este
    | peso cuando el usuario aún no lo registra en su perfil.
    |
    */

    'met_por_defecto' => 5.0,

    /*
    |--------------------------------------------------------------------------
    | Inicio
    |--------------------------------------------------------------------------
    */

    'ultimas_sesiones' => 3,

    /*
    |--------------------------------------------------------------------------
    | Biblioteca de ejercicios
    |--------------------------------------------------------------------------
    |
    | Cuántos ejercicios se muestran por página en el listado.
    |
    */

    'max_ejercicios' => 20,

    /*
    |--------------------------------------------------------------------------
    | Explorar en wger
    |--------------------------------------------------------------------------
    |
    | La sección "Explorar en wger" de la biblioteca lee la API pública de
    | wger (no pide clave), la misma que usaba la versión Flutter. Cada
    | ejercicio trae todas sus traducciones y pesa ~12 KB, así que se pide una
    | sola página y se guarda en caché para no repetir la llamada en cada visita.
    |
    */

    'wger' => [
        'url' => env('GOGYM_WGER_URL', 'https://wger.de/api/v2'),
        'limite' => 60,
        'cache_horas' => 24,
        'timeout_segundos' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Generar rutina con IA (vista 03)
    |--------------------------------------------------------------------------
    |
    | Las opciones del formulario "Arma tu rutina". GenerarRutinaRequest valida
    | contra estas mismas listas, así la vista y la validación nunca se
    | desalinean: si se agrega una opción aquí, aparece en las dos.
    |
    | 'equipamiento' traduce cada chip del formulario a los valores reales de
    | la columna `equipo` del catálogo. Los que terminan en * son prefijos:
    | 'Máquina*' cubre "Máquina Smith", "Máquina de curl femoral", etc.
    |
    */

    'rutina_ia' => [
        'objetivos' => ['Ganar músculo', 'Bajar de peso', 'Salud general'],
        'niveles' => ['Principiante', 'Intermedio', 'Avanzado'],
        'dias' => [2, 3, 4, 5, 6],
        'minutos' => [45, 60, 90],
        'equipamiento' => [
            'Barra' => ['Barra', 'Barra Z', 'Barra hexagonal', 'Discos', 'Banco plano'],
            'Mancuernas' => ['Mancuernas', 'Banco plano'],
            'Máquinas' => ['Máquina*', 'Prensa de piernas', 'Pec deck'],
            'Poleas' => ['Polea'],
            'Kettlebell' => ['Kettlebell'],
            'Solo peso corporal' => ['Sin equipo', 'Barra de dominadas', 'Paralelas'],
        ],
        'max_molestias' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cuenta demo
    |--------------------------------------------------------------------------
    |
    | La crea DemoSeeder con rutinas y dos semanas de entrenamientos.
    |
    */

    'demo' => [
        'email' => env('GOGYM_DEMO_EMAIL', 'demo@gogym.app'),
        'password' => env('GOGYM_DEMO_PASSWORD', 'demo2026'),
    ],

];
