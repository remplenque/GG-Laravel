<?php

namespace App\Support;

use Laravel\Ai\Enums\Lab;

/**
 * Proveedores disponibles para Go Gym, en orden de preferencia.
 *
 * Groq es el principal por velocidad y cuota; Gemini es el respaldo exigido
 * por la pauta. Un proveedor sin clave nunca entra a la cadena, para que una
 * instalación con solo GEMINI_API_KEY también funcione.
 */
class ProveedoresIa
{
    /** @return list<Lab> */
    public function disponibles(): array
    {
        return collect([Lab::Groq, Lab::Gemini])
            ->filter(fn (Lab $proveedor) => filled(config("ai.providers.{$proveedor->value}.key")))
            ->values()
            ->all();
    }

    public function configurada(): bool
    {
        return $this->disponibles() !== [];
    }
}
