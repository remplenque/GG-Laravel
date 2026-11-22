<?php

/*
|--------------------------------------------------------------------------
| Entrada de Go Gym en Vercel
|--------------------------------------------------------------------------
|
| Vercel corre PHP como función (runtime vercel-php) y manda aquí todas las
| peticiones que no son archivos estáticos de public/ (ver vercel.json).
| Es lo mismo que public/index.php, con una diferencia: en Vercel el disco
| es de solo lectura salvo /tmp, así que las vistas compiladas y los
| archivos de caché de Laravel se escriben allí (variables en vercel.json).
|
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

@mkdir('/tmp/views', 0755, true);

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
