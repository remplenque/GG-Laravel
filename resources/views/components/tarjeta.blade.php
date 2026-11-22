{{-- Contenedor redondeado con borde fino, como las tarjetas del mockup.
     variante: normal | destacada (azul, "Toca hoy") | ia (violeta, Coach IA,
     con el borde animado "aura"). --}}
@props(['variante' => 'normal', 'href' => null])

@php
    $clases = match ($variante) {
        'destacada' => 'border-primario/70 bg-primario/[0.07] shadow-[0_0_0_1px_rgb(76_141_255/0.15),0_12px_40px_-20px_rgb(76_141_255/0.6)]',
        'ia' => 'aura border',
        default => 'border-borde bg-tarjeta/85',
    };
    $clases = "tarjeta block rounded-2xl border p-3.5 {$clases}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases.' hover:border-primario/60']) }}>
        {{ $slot }}
    </a>
@else
    <div {{ $attributes->merge(['class' => $clases]) }}>
        {{ $slot }}
    </div>
@endif
