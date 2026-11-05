{{-- Encabezado de las pantallas internas: botón atrás, etiqueta pequeña y título. --}}
@props(['titulo', 'etiqueta' => null, 'subtitulo' => null, 'atras' => null, 'ia' => false])

<header {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    @if ($atras)
        <a href="{{ $atras }}" class="grid size-10 shrink-0 place-items-center rounded-xl border border-borde bg-tarjeta text-texto hover:border-primario/60" aria-label="Volver">
            <x-icono nombre="flecha-izq" />
        </a>
    @endif
    <div class="min-w-0 flex-1">
        @if ($etiqueta)
            <p @class(['etiqueta', 'text-ia' => $ia, 'text-tenue' => ! $ia])>{{ $etiqueta }}</p>
        @endif
        <h1 class="truncate font-display text-xl font-bold">{{ $titulo }}</h1>
        @if ($subtitulo)
            <p class="truncate text-xs text-tenue">{{ $subtitulo }}</p>
        @endif
    </div>
    {{ $slot }}
</header>
