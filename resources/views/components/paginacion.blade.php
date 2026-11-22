{{-- Paginación oscura (Anterior · Página X de Y · Siguiente) para cualquier
     paginador de Laravel. Uso: <x-paginacion :paginas="$ejercicios" class="mt-4" />
     No dibuja nada si hay una sola página. --}}
@props(['paginas'])

@if ($paginas->hasPages())
    <nav {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3']) }} aria-label="Páginas">
        @if ($paginas->previousPageUrl())
            <a href="{{ $paginas->previousPageUrl() }}" rel="prev"
               class="presionable flex items-center gap-1 rounded-xl border border-borde bg-tarjeta px-3 py-2 text-xs font-medium transition hover:border-primario/60">
                <x-icono nombre="flecha-izq" class="size-4" /> Anterior
            </a>
        @else
            <span class="flex items-center gap-1 rounded-xl border border-borde/60 px-3 py-2 text-xs text-tenue/50">
                <x-icono nombre="flecha-izq" class="size-4" /> Anterior
            </span>
        @endif

        <span class="text-xs tabular-nums text-tenue">Página {{ $paginas->currentPage() }} de {{ $paginas->lastPage() }}</span>

        @if ($paginas->nextPageUrl())
            <a href="{{ $paginas->nextPageUrl() }}" rel="next"
               class="presionable flex items-center gap-1 rounded-xl border border-borde bg-tarjeta px-3 py-2 text-xs font-medium transition hover:border-primario/60">
                Siguiente <x-icono nombre="flecha-der" class="size-4" />
            </a>
        @else
            <span class="flex items-center gap-1 rounded-xl border border-borde/60 px-3 py-2 text-xs text-tenue/50">
                Siguiente <x-icono nombre="flecha-der" class="size-4" />
            </span>
        @endif
    </nav>
@endif
