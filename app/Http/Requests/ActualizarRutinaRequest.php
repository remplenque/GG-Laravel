<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Valida el formulario "Editar rutina" (PUT /rutinas/{rutina}).
 *
 * Los ejercicios llegan agrupados por el id de su fila en rutina_ejercicios:
 *   ejercicios[12][series] = 4, ejercicios[12][repeticiones] = 8,
 *   ejercicios[12][descanso_seg] = 90, ejercicios[12][quitar] = 1 (si se marcó)
 * así RutinaController@actualizar sabe a qué fila corresponde cada valor.
 */
class ActualizarRutinaRequest extends FormRequest
{
    /**
     * Solo el dueño puede editarla (RutinaPolicy@update). $this->route('rutina')
     * es la rutina que Laravel ya buscó por el {rutina} de la URL.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('rutina'));
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:40'],
            'ejercicios' => ['array'],
            'ejercicios.*.series' => ['required', 'integer', 'between:1,10'],
            // 0 = el ejercicio va por tiempo (la carga dice cuánto, p. ej. "60 s").
            'ejercicios.*.repeticiones' => ['required', 'integer', 'between:0,50'],
            'ejercicios.*.descanso_seg' => ['required', 'integer', 'between:0,600'],
            // Un checkbox sin marcar no se envía; marcado manda "1".
            'ejercicios.*.quitar' => ['nullable', 'boolean'],
        ];
    }

    /** Nombres en español para los mensajes de error. */
    public function attributes(): array
    {
        return [
            'ejercicios.*.series' => 'series',
            'ejercicios.*.repeticiones' => 'repeticiones',
            'ejercicios.*.descanso_seg' => 'descanso',
        ];
    }
}
