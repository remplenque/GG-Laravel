<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarEntrenamientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rutina_id' => ['nullable', 'integer', Rule::exists('rutinas', 'id')],
            'nombre' => ['required', 'string', 'max:80'],
            'inicio' => ['required', 'date'],
            'duracion' => ['required', 'integer', 'between:1,600'],
            'ejercicios' => ['required', 'array', 'min:1', 'max:10'],
            'ejercicios.*.ejercicio_id' => ['required', 'string', 'distinct', Rule::exists('ejercicios', 'id')],
            'ejercicios.*.series' => ['required', 'array', 'min:1', 'max:8'],
            'ejercicios.*.series.*.kg' => ['nullable', 'numeric', 'between:0,9999.99'],
            'ejercicios.*.series.*.reps' => ['nullable', 'integer', 'between:1,1000'],
            'ejercicios.*.series.*.rpe' => ['nullable', 'integer', 'between:1,10'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ($this->input('ejercicios', []) as $indice => $ejercicio) {
                if (! is_array($ejercicio) || ! is_array($ejercicio['series'] ?? null)) {
                    continue;
                }

                $completas = collect($ejercicio['series'])->filter(
                    fn ($serie) => is_array($serie) && filled($serie['reps'] ?? null)
                );

                if ($completas->isEmpty()) {
                    $validator->errors()->add(
                        "ejercicios.{$indice}.series",
                        'Registra al menos una serie con repeticiones.'
                    );
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribe un nombre para la sesión.',
            'inicio.required' => 'Indica cuándo comenzó el entrenamiento.',
            'duracion.between' => 'La duración debe estar entre 1 y 600 minutos.',
            'ejercicios.required' => 'Elige al menos un ejercicio.',
            'ejercicios.*.series.*.kg.numeric' => 'El peso debe ser un número.',
            'ejercicios.*.series.*.reps.integer' => 'Las repeticiones deben ser un número entero.',
            'ejercicios.*.series.*.rpe.between' => 'El RPE debe estar entre 1 y 10.',
        ];
    }
}
