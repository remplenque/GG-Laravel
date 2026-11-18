<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class SugerenciaAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'TEXTO'
            Eres el Coach IA de Go Gym. A partir del resumen anónimo del historial, escribe una recomendación de entrenamiento en español neutro.
            Devuelve una o dos frases, máximo 55 palabras, sin Markdown, títulos ni emojis.
            Sé concreto y conservador: sugiere cargas solo cuando el historial las respalda y nunca diagnostiques lesiones.
            No menciones que recibiste datos ni inventes ejercicios, pesos o fechas.
            TEXTO;
    }
}
