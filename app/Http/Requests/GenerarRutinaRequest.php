<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el formulario "Arma tu rutina" (vista 03) antes de que llegue al
 * controlador. Si algo falla, Laravel vuelve SOLO al formulario con los
 * errores y lo escrito (old()), sin entrar a RutinaController@proponer.
 *
 * Las opciones válidas salen de config/gogym.php → 'rutina_ia', las mismas
 * con que se dibuja el formulario.
 */
class GenerarRutinaRequest extends FormRequest
{
    /**
     * Quién puede enviar el formulario. La ruta ya exige sesión ('auth'),
     * así que cualquier cuenta con sesión puede.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de cada campo del formulario.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $opciones = config('gogym.rutina_ia');

        return [
            // Rule::in arma "in:Ganar músculo,Bajar de peso,…" desde la config,
            // sin escribir la lista a mano.
            'objetivo' => ['required', Rule::in($opciones['objetivos'])],
            'nivel' => ['required', Rule::in($opciones['niveles'])],
            'dias' => ['required', 'integer', Rule::in($opciones['dias'])],
            'minutos' => ['required', 'integer', Rule::in($opciones['minutos'])],

            // Checkboxes con name="equipamiento[]": llega un arreglo con al
            // menos un chip, y el .* revisa que cada elemento sea una LLAVE
            // del mapa ("Barra", "Mancuernas"…).
            'equipamiento' => ['required', 'array', 'min:1'],
            'equipamiento.*' => ['string', Rule::in(array_keys($opciones['equipamiento']))],

            'molestias' => ['nullable', 'string', 'max:'.$opciones['max_molestias']],
        ];
    }

    /**
     * Nombres en español para los mensajes de error: "El campo días es
     * obligatorio" en vez de "El campo dias es obligatorio".
     */
    public function attributes(): array
    {
        return [
            'dias' => 'días por semana',
            'minutos' => 'minutos por sesión',
            'equipamiento' => 'equipamiento disponible',
            'equipamiento.*' => 'equipamiento',
            'molestias' => 'molestias o preferencias',
        ];
    }
}
