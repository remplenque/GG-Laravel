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
        'calendario' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'objetivo' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
        'balanza' => '<path d="M5 20h14a2 2 0 0 0 2-2V7a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v11a2 2 0 0 0 2 2z"/><path d="M8 9a4 4 0 0 1 8 0M12 9l2-2"/>',
        'reloj' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'buscar' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'enviar' => '<path d="M3.7 3.3a.7.7 0 0 1 .9-.2l16 8.3a.7.7 0 0 1 0 1.2l-16 8.3a.7.7 0 0 1-1-.8L6 12 3.6 4.1a.7.7 0 0 1 .1-.8z"/><path d="M6 12h8"/>',
        'candado' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'cerrar' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'alerta' => '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
        'trofeo' => '<path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0z"/><path d="M17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/>',
        'cronometro' => '<circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 1.5M10 2h4M19 6l1.5-1.5"/>',
        'editar' => '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
        'basura' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13M9 7V4h6v3"/>',
        'ojo' => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'ojo-cerrado' => '<path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.2A10 10 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4M6.6 6.6A17 17 0 0 0 2 12s3.6 7 10 7a9.8 9.8 0 0 0 5.4-1.6"/>',
        'grafico' => '<path d="M3 3v18h18"/><path d="M7 15v2M11 11v6M15 7v10M19 12v5"/>',
        'refrescar' => '<path d="M20 11a8 8 0 0 0-14.9-3.9L3 9.5M3 4v5.5h5.5M4 13a8 8 0 0 0 14.9 3.9L21 14.5M21 20v-5.5h-5.5"/>',
        'pausa' => '<path d="M8 5v14M16 5v14"/>',
        'cohete' => '<path d="M5 15c-1.5 1.3-2 5-2 5s3.7-.5 5-2c.7-.8.7-2.1-.1-2.9a2.1 2.1 0 0 0-2.9-.1z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.9A12.9 12.9 0 0 1 22 2c0 2.7-.8 7.5-6 11a22.4 22.4 0 0 1-4 2z"/><path d="M9 12H4s.6-3 2-4c1.6-1.1 5 0 5 0M12 15v5s3-.6 4-2c1.1-1.6 0-5 0-5"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'size-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $trazos[$nombre] ?? '' !!}
</svg>
