<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class RutinaAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'TEXTO'
            Eres el generador de rutinas de Go Gym. Diseñas planes conservadores para personas adultas sanas.
            Debes respetar exactamente los días, el tiempo, nivel, objetivo, equipamiento y molestias indicados.
            Elige ejercicios solo de la lista permitida incluida en el mensaje y copia sus identificadores sin alterarlos.
            Si se informa dolor o una lesión, evita cargar esa zona y deja en la explicación la recomendación de consultar a un profesional.
            No incluyas dietas, suplementos, diagnósticos ni ejercicios que no estén en la lista.
            TEXTO;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'nombre' => $schema->string()->required(),
            'explicacion' => $schema->string()->required(),
            'dias' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema) => [
                    'nombre' => $schema->string()->required(),
                    'ejercicios' => $schema->array()->items(
                        $schema->object(fn (JsonSchema $schema) => [
                            'ejercicio_id' => $schema->string()->required(),
                            'series' => $schema->integer()->required(),
                            'repeticiones' => $schema->integer()->required(),
                            'descanso_seg' => $schema->integer()->required(),
                            'rpe' => $schema->integer()->required(),
                            'nota' => $schema->string()->nullable()->required(),
                        ])
                    )->required(),
                ])
            )->required(),
        ];
    }
}
