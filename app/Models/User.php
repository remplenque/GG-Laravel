<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'apellido', 'email', 'password',
    'objetivo', 'nivel', 'peso_kg', 'dias_entrenamiento', 'hora_entrenamiento',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'peso_kg' => 'float',
            'dias_entrenamiento' => 'array',
        ];
    }

    /** @return HasMany<Rutina, $this> */
    public function rutinas(): HasMany
    {
        return $this->hasMany(Rutina::class)->orderBy('id');
    }

    /** @return HasMany<Entrenamiento, $this> */
    public function entrenamientos(): HasMany
    {
        return $this->hasMany(Entrenamiento::class)->latest('inicio');
    }

    /** "VR" para el avatar; vacío si todavía no hay nombre. */
    protected function iniciales(): Attribute
    {
        return Attribute::get(fn () => Str::upper(
            Str::substr(trim($this->name), 0, 1).Str::substr(trim($this->apellido ?? ''), 0, 1)
        ));
    }

    public function esDiaEntrenamiento(CarbonInterface $fecha): bool
    {
        return in_array($fecha->dayOfWeekIso - 1, $this->dias_entrenamiento ?? [], true);
    }

    /** El siguiente día de entrenamiento después de $fecha (sin incluirla). */
    public function proximoDiaEntrenamiento(CarbonInterface $fecha): ?CarbonInterface
    {
        for ($i = 1; $i <= 7; $i++) {
            $dia = $fecha->toImmutable()->startOfDay()->addDays($i);
            if ($this->esDiaEntrenamiento($dia)) {
                return $dia;
            }
        }

        return null;
    }
}
