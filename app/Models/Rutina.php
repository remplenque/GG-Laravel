<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['nombre', 'categoria', 'nivel', 'minutos'])]
class Rutina extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<RutinaEjercicio, $this> */
    public function ejercicios(): HasMany
    {
        return $this->hasMany(RutinaEjercicio::class)->orderBy('orden');
    }

    /**
     * Rotación: la rutina que sigue a la última entrenada según el historial
     * (más reciente primero). Sin historial, la primera. Solo cuentan las que
     * tienen ejercicios.
     *
     * @param  Collection<int, Rutina>  $rutinas
     * @param  iterable<Entrenamiento>  $historial
     */
    public static function siguiente(Collection $rutinas, iterable $historial): ?self
    {
        $disponibles = $rutinas->filter(fn (self $r) => $r->ejercicios->isNotEmpty())->values();
        if ($disponibles->isEmpty()) {
            return null;
        }

        foreach ($historial as $entrenamiento) {
            $indice = $disponibles->search(fn (self $r) => $r->id === $entrenamiento->rutina_id);
            if ($indice !== false) {
                return $disponibles[($indice + 1) % $disponibles->count()];
            }
        }

        return $disponibles->first();
    }

    /** Grupos musculares sin repetir, en el orden de los ejercicios. */
    protected function grupos(): Attribute
    {
        return Attribute::get(fn () => $this->ejercicios
            ->map(fn (RutinaEjercicio $e) => $e->ejercicio?->grupo)
            ->filter()
            ->unique()
            ->values()
            ->all());
    }

    /** "Push A · Pecho y hombro": nombre y los dos primeros grupos musculares. */
    protected function titulo(): Attribute
    {
        return Attribute::get(function () {
            $grupos = array_slice($this->grupos, 0, 2);
            $detalle = $grupos === []
                ? $this->categoria
                : collect($grupos)->map(fn ($g, $i) => $i === 0 ? $g : Str::lower($g))->join(' y ');

            return $detalle === '' ? $this->nombre : "{$this->nombre} · {$detalle}";
        });
    }

    /** "6 ejercicios · 55 min · Intermedio", sin las partes que falten. */
    protected function resumen(): Attribute
    {
        return Attribute::get(function () {
            $cantidad = $this->ejercicios->count();

            return collect([
                $cantidad.' '.($cantidad === 1 ? 'ejercicio' : 'ejercicios'),
                $this->minutos > 0 ? $this->minutos.' min' : null,
                $this->nivel ?: null,
            ])->filter()->join(' · ');
        });
    }
}
