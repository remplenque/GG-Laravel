<?php

namespace App\Policies;

use App\Models\Rutina;
use App\Models\User;

/**
 * Quién puede hacer qué con una rutina. Laravel la usa sola cuando un
 * controlador llama a Gate::authorize('accion', $rutina): si el método
 * devuelve false, responde 403 y no sigue.
 */
class RutinaPolicy
{
    /** Cada cuenta ve solo sus rutinas. */
    public function view(User $user, Rutina $rutina): bool
    {
        return $rutina->user_id === $user->id;
    }

    /** Solo el dueño la edita o le agrega ejercicios. */
    public function update(User $user, Rutina $rutina): bool
    {
        return $rutina->user_id === $user->id;
    }

    /** Solo el dueño la borra. */
    public function delete(User $user, Rutina $rutina): bool
    {
        return $rutina->user_id === $user->id;
    }
}
