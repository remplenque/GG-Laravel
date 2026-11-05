{{-- Íconos de trazo (estilo Lucide). Uso: <x-icono nombre="inicio" class="size-5" /> --}}
@props(['nombre'])

@php
    $trazos = [
        'inicio' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>',
        'lista' => '<path d="M8 6h13M8 12h13M8 18h13"/><path d="M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
        'mas' => '<path d="M12 5v14M5 12h14"/>',
        'chispas' => '<path d="M9.94 15.5a2 2 0 0 0-1.44-1.44l-6.13-1.58a.5.5 0 0 1 0-.96L8.5 9.94A2 2 0 0 0 9.94 8.5l1.58-6.14a.5.5 0 0 1 .96 0l1.58 6.14a2 2 0 0 0 1.44 1.44l6.13 1.58a.5.5 0 0 1 0 .96l-6.13 1.58a2 2 0 0 0-1.44 1.44l-1.58 6.13a.5.5 0 0 1-.96 0z"/><path d="M20 3v4M22 5h-4"/>',
        'usuario' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'llama' => '<path d="M12 22c4 0 7-3 7-7 0-4.5-4-7-5-11-2 2-3 4-3 6-1.5-1-2-2.5-2-4C6 8 5 11 5 15c0 4 3 7 7 7z"/>',
        'flecha-der' => '<path d="m9 6 6 6-6 6"/>',
        'flecha-izq' => '<path d="m15 6-6 6 6 6"/>',
        'play' => '<path d="M7 4.5v15l12-7.5z" fill="currentColor"/>',
        'pesa' => '<path d="M6.5 6.5v11M17.5 6.5v11M3.5 9v6M20.5 9v6M6.5 12h11"/>',
        'correr' => '<circle cx="14" cy="4.5" r="1.5"/><path d="m7 21 3-6 3 2v4M6 12l3-4 4 1 3 4h3M10 8l-2 7"/>',
        'calma' => '<circle cx="12" cy="5" r="2"/><path d="M4 20c2-1 4-1.5 8-1.5s6 .5 8 1.5M12 9v5M7 12l5 2 5-2"/>',
        'rayo' => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        'imagen' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/>',
        'libro' => '<path d="M4 19.5V5a2 2 0 0 1 2-2h14v16H6a2 2 0 0 0-2 2zM20 19v3H6"/>',
        'salir' => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l-5-5 5-5M5 12h11"/>',
        'herramienta' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'size-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $trazos[$nombre] ?? '' !!}
</svg>
