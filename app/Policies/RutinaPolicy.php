<?php

namespace App\Policies;

use App\Models\Rutina;
use App\Models\User;

class RutinaPolicy
{
    /** Cada cuenta ve solo sus rutinas. */
    public function view(User $user, Rutina $rutina): bool
    {
        return $rutina->user_id === $user->id;
    }
}
