<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida "Editar perfil" (PUT /perfil). El objetivo y el nivel usan las
 * mismas listas que el formulario de la rutina con IA (config/gogym.php),
 * así el coach siempre recibe valores que conoce.
 */
class ActualizarPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $opciones = config('gogym.rutina_ia');

        return [
            'name' => ['required', 'string', 'max:60'],
            'apellido' => ['nullable', 'string', 'max:60'],
            'objetivo' => ['nullable', Rule::in($opciones['objetivos'])],
            'nivel' => ['nullable', Rule::in($opciones['niveles'])],
            'peso_kg' => ['nullable', 'numeric', 'between:30,300'],
            // 0 = lunes … 6 = domingo, como en la columna dias_entrenamiento.
            'dias_entrenamiento' => ['nullable', 'array', 'max:7'],
            'dias_entrenamiento.*' => ['integer', 'between:0,6'],
            'hora_entrenamiento' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'peso_kg' => 'peso',
            'dias_entrenamiento' => 'días de entrenamiento',
            'dias_entrenamiento.*' => 'día',
            'hora_entrenamiento' => 'horario',
        ];
    }
}
