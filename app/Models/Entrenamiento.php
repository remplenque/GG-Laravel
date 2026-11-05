<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un entrenamiento (sesión) del usuario con las series que hizo.
 */
#[Fillable(['rutina_id', 'nombre', 'inicio', 'fin'])]
class Entrenamiento extends Model
{
    protected function casts(): array
    {
        return [
            'inicio' => 'immutable_datetime',
            'fin' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Rutina, $this> */
    public function rutina(): BelongsTo
    {
        return $this->belongsTo(Rutina::class);
    }

    /** @return HasMany<EntrenamientoSerie, $this> */
    public function series(): HasMany
    {
        return $this->hasMany(EntrenamientoSerie::class)->orderBy('orden')->orderBy('numero');
    }

    /** Suma de kg × reps de todas las series. */
    protected function volumen(): Attribute
    {
        return Attribute::get(fn () => $this->series->sum(fn (EntrenamientoSerie $s) => $s->volumen));
    }

    protected function duracionMinutos(): Attribute
    {
        return Attribute::get(fn () => $this->fin === null
            ? 0
            : max(0, (int) round($this->inicio->diffInSeconds($this->fin) / 60)));
    }

    /** El grupo muscular con más series; null si no hay ejercicios con grupo. */
    protected function grupoPrincipal(): Attribute
    {
        return Attribute::get(fn () => $this->series
            ->map(fn (EntrenamientoSerie $s) => $s->ejercicio?->grupo)
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first());
    }

    /** "Pull A · Espalda"; sin el grupo si ya está en el nombre. */
    protected function titulo(): Attribute
    {
        return Attribute::get(function () {
            $grupo = $this->grupo_principal;

            return $grupo === null || str_contains($this->nombre, $grupo)
                ? $this->nombre
                : "{$this->nombre} · {$grupo}";
        });
    }

    /** MET medio de la sesión, ponderado por series. */
    protected function met(): Attribute
    {
        return Attribute::get(function () {
            $porDefecto = config('gogym.met_por_defecto');

            return $this->series->isEmpty()
                ? $porDefecto
                : $this->series->avg(fn (EntrenamientoSerie $s) => $s->ejercicio?->met ?? $porDefecto);
        });
    }

    /** Estimación de la quema: MET × peso × horas. Es aproximada, no exacta. */
    public function kcalEstimadas(float $pesoKg): float
    {
        return $this->met * $pesoKg * $this->duracion_minutos / 60;
    }
}
