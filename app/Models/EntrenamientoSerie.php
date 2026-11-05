<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ejercicio_id', 'orden', 'numero', 'kg', 'reps', 'rpe'])]
class EntrenamientoSerie extends Model
{
    protected $table = 'entrenamiento_series';

    protected function casts(): array
    {
        return [
            'kg' => 'float',
            'reps' => 'integer',
            'rpe' => 'integer',
        ];
    }

    /** @return BelongsTo<Entrenamiento, $this> */
    public function entrenamiento(): BelongsTo
    {
        return $this->belongsTo(Entrenamiento::class);
    }

    /** @return BelongsTo<Ejercicio, $this> */
    public function ejercicio(): BelongsTo
    {
        return $this->belongsTo(Ejercicio::class);
    }

    /** kg × reps; 0 si falta alguno de los dos. */
    protected function volumen(): Attribute
    {
        return Attribute::get(fn () => ($this->kg ?? 0) * ($this->reps ?? 0));
    }
}
