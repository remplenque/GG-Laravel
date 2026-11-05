{{-- Título de sección en versalitas, con una acción opcional a la derecha. --}}
@props(['texto', 'accion' => null, 'href' => null])

<div {{ $attributes->merge(['class' => 'flex items-center justify-between']) }}>
    <h2 class="etiqueta text-tenue">{{ $texto }}</h2>
    @if ($accion)
        <a href="{{ $href }}" class="text-xs font-medium text-primario hover:underline">{{ $accion }}</a>
    @endif
</div>
