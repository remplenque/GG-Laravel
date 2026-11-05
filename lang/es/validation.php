<?php

// Solo las reglas que usa la app; el resto cae en el idioma de respaldo.
return [
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'current_password' => 'La contraseña es incorrecta.',
    'email' => ':Attribute debe ser un correo válido.',
    'lowercase' => ':Attribute debe estar en minúsculas.',
    'max' => [
        'numeric' => ':Attribute no puede ser mayor que :max.',
        'string' => ':Attribute no puede tener más de :max caracteres.',
    ],
    'min' => [
        'numeric' => ':Attribute debe ser al menos :min.',
        'string' => ':Attribute debe tener al menos :min caracteres.',
    ],
    'password' => [
        'letters' => ':Attribute debe tener al menos una letra.',
        'mixed' => ':Attribute debe tener mayúsculas y minúsculas.',
        'numbers' => ':Attribute debe tener al menos un número.',
        'symbols' => ':Attribute debe tener al menos un símbolo.',
        'uncompromised' => 'Esta :attribute apareció en una filtración. Elige otra.',
    ],
    'required' => ':Attribute es obligatorio.',
    'string' => ':Attribute debe ser texto.',
    'unique' => 'Ya existe una cuenta con este :attribute.',

    'attributes' => [
        'name' => 'nombre',
        'apellido' => 'apellido',
        'email' => 'correo',
        'password' => 'contraseña',
    ],
];
