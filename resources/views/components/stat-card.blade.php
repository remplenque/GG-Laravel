{{--
    Componente <x-stat-card>: un número grande con su etiqueta ("8.240 kg / Volumen").

    Props:
      - valor:   el número ya formateado que se muestra ("8.240").
      - etiqueta: el texto chico de abajo ("Volumen").
      - unidad:  opcional, va al lado del número en gris ("kg").
      - contar:  opcional, el número SIN formato (8240). Si viene, el número
                 cuenta desde 0 al cargar la página (directiva x-contar de
                 resources/js/app.js). Si no viene, se muestra fijo.
      - retraso: milisegundos antes de empezar a contar (por defecto 0).

    Los demás atributos (class, style…) se pasan a la tarjeta.
--}}
@props(['valor', 'etiqueta', 'unidad' => null, 'contar' => null, 'retraso' => 0])

<x-tarjeta {{ $attributes->merge(['class' => 'px-3 py-3']) }}>
    {{-- tabular-nums: todas las cifras miden lo mismo, así el número no
         "baila" de ancho mientras cuenta. --}}
    <p class="truncate text-xl font-bold tabular-nums">
        @if ($contar !== null)
            {{-- x-data activa Alpine en este <span>; x-contar.{retraso} hace
                 la cuenta. Adentro va el valor final por si no hay JS. --}}
            <span x-data x-contar.{{ (int) $retraso }}="{{ $contar }}">{{ $valor }}</span>
        @else
            {{ $valor }}
        @endif
        @if ($unidad)<span class="ml-1 text-xs font-medium text-tenue">{{ $unidad }}</span>@endif
    </p>
    <p class="mt-0.5 text-xs text-tenue">{{ $etiqueta }}</p>
</x-tarjeta>
