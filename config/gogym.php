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
