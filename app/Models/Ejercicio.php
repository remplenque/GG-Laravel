<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

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

    /** URL completa de la imagen, o null si el ejercicio no tiene. */
    protected function urlImagen(): Attribute
    {
        return Attribute::get(fn () => $this->imagen === null
            ? null
            : config('gogym.imagenes_url').$this->imagen);
    }
}
