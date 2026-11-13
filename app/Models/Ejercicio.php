<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Un ejercicio del catálogo. Solo lectura para la app.
 */
class Ejercicio extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'peso_corporal' => 'boolean',
            'musculos' => 'array',
            'musculos_secundarios' => 'array',
            'met' => 'float',
            'instrucciones' => 'array',
            'consejos' => 'array',
        ];
    }

    /**
     * Texto normalizado para ordenar: en minúsculas y sin tildes.
     * "Élevación Lateral" → "elevacion lateral". Se guarda en la columna
     * nombre_orden para que la lista salga en orden alfabético real.
     * Esto porque sino se priorizan las mayúsculas y las tildes y el orden no es el que esperado.
     * Se usa en EjercicioController::index() para ordenar la lista de ejercicios
     * y para buscar sin que importen las tildes ni las mayúsculas.
     */
    public static function normalizar(string $texto): string
    {
        // Str::ascii cambia las letras con tilde por su versión simple (á → a, ñ → n)
        // y Str::lower pasa todo a minúsculas.
        return Str::lower(Str::ascii($texto));
    }

    /** URL completa de la imagen, o null si el ejercicio no tiene. */
    protected function urlImagen(): Attribute
    {
        return Attribute::get(fn () => $this->imagen === null
            ? null
            : config('gogym.imagenes_url').$this->imagen);
    }
}
