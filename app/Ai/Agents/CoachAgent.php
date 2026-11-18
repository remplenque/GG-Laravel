<?php

namespace App\Ai\Agents;

use App\Models\User;
use App\Support\Coach;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Stringable;

class CoachAgent implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    public function __construct(public User $user) {}

    public function instructions(): Stringable|string
    {
        $coach = app(Coach::class);

        return $coach->instrucciones()."\n\n".$coach->contexto($this->user);
    }

    /** Envía solo los últimos mensajes para cuidar cuota y privacidad. */
    protected function maxConversationMessages(): int
    {
        return Coach::MAX_HISTORIAL;
    }
}
