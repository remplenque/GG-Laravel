{{-- Contenedor redondeado con borde fino, como las tarjetas del mockup.
     variante: normal | destacada (azul, "Toca hoy") | ia (violeta, Coach IA). --}}
@props(['variante' => 'normal', 'href' => null])

@php
    $clases = match ($variante) {
        'destacada' => 'border-primario/80 bg-primario/8',
        'ia' => 'border-ia-borde bg-ia-fondo',
        default => 'border-borde bg-tarjeta',
    };
    $clases = "block rounded-2xl border p-3.5 {$clases}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases.' transition hover:border-primario/60 active:scale-[0.98]']) }}>
        {{ $slot }}
    </a>
@else
    <div {{ $attributes->merge(['class' => $clases]) }}>
        {{ $slot }}
    </div>
@endif
