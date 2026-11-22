{{--
    Componente <x-stat-card>: un número grande con su etiqueta ("8.240 kg / Volumen").

    Props:
      - valor:   el número ya formateado que se muestra ("8.240").
      - etiqueta: el texto chico de abajo ("Volumen").
      - unidad:  opcional, va al lado del número en gris ("kg").
      - contar:  opcional, el número SIN formato (8240). Si viene, el número
                 cuenta desde 0 al cargar la página (directiva x-contar).
      - retraso: milisegundos antes de empezar a contar (por defecto 0).
      - icono:   opcional, ícono chico arriba a la derecha.

    Los demás atributos (class, style…) se pasan a la tarjeta.
--}}
@props(['valor', 'etiqueta', 'unidad' => null, 'contar' => null, 'retraso' => 0, 'icono' => null])

<x-tarjeta {{ $attributes->merge(['class' => 'relative px-3 py-3']) }}>
    @if ($icono)
        <x-icono :nombre="$icono" class="absolute right-2.5 top-2.5 hidden size-3.5 text-tenue/60 sm:block" />
    @endif
    {{-- tabular-nums: todas las cifras miden lo mismo, así el número no
         "baila" de ancho mientras cuenta. --}}
    <p class="truncate font-display text-lg font-bold tracking-tight tabular-nums sm:text-xl lg:text-2xl">
        @if ($contar !== null)
            <span x-data x-contar.{{ (int) $retraso }}="{{ $contar }}">{{ $valor }}</span>
        @else
            {{ $valor }}
        @endif
        @if ($unidad)<span class="ml-0.5 font-sans text-[11px] font-medium tracking-normal text-tenue">{{ $unidad }}</span>@endif
    </p>
    <p class="mt-0.5 text-xs text-tenue">{{ $etiqueta }}</p>
</x-tarjeta>
