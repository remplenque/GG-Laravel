{{-- Un número grande con su etiqueta: "8.240 kg / Volumen". --}}
@props(['valor', 'etiqueta', 'unidad' => null])

<x-tarjeta class="px-3 py-3">
    <p class="truncate text-xl font-bold">
        {{ $valor }}@if ($unidad)<span class="ml-1 text-xs font-medium text-tenue">{{ $unidad }}</span>@endif
    </p>
    <p class="mt-0.5 text-xs text-tenue">{{ $etiqueta }}</p>
</x-tarjeta>
