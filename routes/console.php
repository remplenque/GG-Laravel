<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Crea el schema de Postgres de la app (DB_SCHEMA) si no existe. Corre antes
 * de migrar en Vercel (script "vercel" de composer.json): migrate guarda su
 * tabla "migrations" en ese schema, así que tiene que existir antes.
 * Con SQLite o con el schema "public" no hace nada.
 */
Artisan::command('gogym:esquema', function () {
    $conexion = DB::connection();
    $esquema = config('database.connections.pgsql.search_path', 'public');

    if ($conexion->getDriverName() !== 'pgsql' || $esquema === 'public') {
        $this->info('Nada que crear.');

        return;
    }

    if (! preg_match('/^[a-z_][a-z0-9_]*$/', $esquema)) {
        $this->error("Nombre de schema no válido: {$esquema}");

        return 1;
    }

    // Si ya existe no se intenta crear: el rol de la app (gogym_app en
    // Supabase) puede usar su schema pero no crear schemas nuevos.
    $existe = $conexion->selectOne('select 1 as existe from pg_namespace where nspname = ?', [$esquema]);
    if ($existe === null) {
        $conexion->statement("CREATE SCHEMA \"{$esquema}\"");
    }

    $this->info("Schema {$esquema} listo.");
})->purpose('Crea el schema de Postgres de Go Gym (DB_SCHEMA) si no existe');
