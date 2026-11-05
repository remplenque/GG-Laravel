{{-- Contenido temporal de las vistas que aún no se implementan (equivale a
     PaginaEnConstruccion en Flutter). Reemplazar al construir cada vista. --}}
@props(['vista', 'ia' => false])

<div {{ $attributes->merge(['class' => 'mt-16 flex flex-col items-center px-6 text-center']) }}>
    <span @class([
        'grid size-16 place-items-center rounded-full',
        'bg-ia/15 text-ia' => $ia,
        'bg-primario/12 text-primario' => ! $ia,
    ])>
        <x-icono nombre="herramienta" class="size-7" />
    </span>
    <p class="mt-4 font-display text-lg font-bold">En construcción</p>
    <p class="mt-1 text-sm text-tenue">{{ $vista }}</p>
    @if ($slot->isNotEmpty())
        <div class="mt-3 text-sm text-tenue">{{ $slot }}</div>
    @endif
</div>
