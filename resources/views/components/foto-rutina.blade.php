{{-- Portada de una rutina: franjas diagonales con el ícono de su categoría. --}}
@props(['categoria' => ''])

@php
    $icono = match (mb_strtolower($categoria)) {
        'cardio' => 'correr',
        'movilidad', 'flexibilidad', 'yoga', 'estiramiento' => 'calma',
        'hiit', 'funcional', 'pliometría' => 'rayo',
        default => 'pesa',
    };
@endphp

<div {{ $attributes->merge(['class' => 'franjas grid place-items-center overflow-hidden text-primario/80']) }}>
    <x-icono :nombre="$icono" class="size-6" />
</div>
