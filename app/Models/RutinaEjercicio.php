<?php

namespace App\Models;

use App\Support\Formato;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ejercicio_id', 'orden', 'series', 'repeticiones', 'carga', 'descanso_seg', 'nota'])]
class RutinaEjercicio extends Model
{
    /** @return BelongsTo<Rutina, $this> */
    public function rutina(): BelongsTo
    {
        return $this->belongsTo(Rutina::class);
    }

    /** @return BelongsTo<Ejercicio, $this> */
    public function ejercicio(): BelongsTo
    {
        return $this->belongsTo(Ejercicio::class);
    }

    /** "4 × 8 · 45 kg", "3 × 10 · Peso corporal" o "3 series · 60 s". */
    protected function resumen(): Attribute
    {
        return Attribute::get(function () {
            $volumen = $this->repeticiones > 0
                ? "{$this->series} × {$this->repeticiones}"
                : $this->series.' '.($this->series === 1 ? 'serie' : 'series');

            return $this->carga === '' ? $volumen : "{$volumen} · {$this->carga}";
        });
    }

    /** "descanso 90 s" o "descanso 2 min"; null si no tiene. */
    protected function descanso(): Attribute
    {
        return Attribute::get(fn () => $this->descanso_seg > 0
            ? 'descanso '.Formato::segundos($this->descanso_seg)
            : null);
    }
}
