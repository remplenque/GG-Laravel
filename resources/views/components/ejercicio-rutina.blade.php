{{--
    Componente <x-ejercicio-rutina>: un ejercicio planificado dentro de una
    rutina, como las filas numeradas de la vista 04 del mockup.

    Se usa en el detalle de una rutina (rutinas/show) y está pensado para
    reutilizarse en la vista "Rutina generada" por la IA, que muestra las mismas
    filas: por eso recibe textos sueltos y no un modelo.

    Uso:
        <x-ejercicio-rutina :numero="1" nombre="Press banca"
                            detalle="4 × 8 · descanso 2 min" lateral="RPE 7"
                            nota="Ajustado por tu hombro…" :href="route(...)" />

    Props (lo que se le pasa como atributo):
      - numero:  el número del cuadrito azul (posición en la rutina).
      - nombre:  el nombre del ejercicio.
      - detalle: la línea chica de abajo (series × reps · carga · descanso).
      - lateral: texto chico a la derecha (el grupo muscular, o el RPE). Opcional.
      - nota:    una aclaración que va dentro de la tarjeta. Opcional.
      - href:    si viene, toda la tarjeta es un enlace (p. ej. a la ficha). Opcional.

    Cualquier otro atributo (class, x-show de Alpine…) se agrega a la tarjeta
    con $attributes->merge().
--}}
@props(['numero', 'nombre', 'detalle' => null, 'lateral' => null, 'nota' => null, 'href' => null])

@php
    // Si hay enlace la tarjeta es un <a>; si no, un <div>. Así se escribe el
    // contenido una sola vez y solo cambia la etiqueta de afuera.
    $etiqueta = $href ? 'a' : 'div';
@endphp

<{{ $etiqueta }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'block rounded-2xl border border-borde bg-tarjeta p-3 transition'.($href ? ' hover:border-primario/60 active:scale-[0.99]' : '')]) }}>

    <div class="flex items-center gap-3">
        {{-- Número del ejercicio en un cuadrado azul. --}}
        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-primario-fuerte text-xs font-bold text-white">
            {{ $numero }}
        </span>

        {{-- min-w-0 + truncate: si el nombre es muy largo se corta con "…"
             en vez de empujar el texto de la derecha fuera de la tarjeta. --}}
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold">{{ $nombre }}</p>
            @if ($detalle)
                <p class="mt-0.5 text-xs text-tenue">{{ $detalle }}</p>
            @endif
        </div>

        @if ($lateral)
            <span class="shrink-0 text-[11px] text-tenue">{{ $lateral }}</span>
        @endif
    </div>

    {{-- La nota va debajo, alineada con el nombre (ml-10 = ancho del número
         más el espacio entre ambos). --}}
    @if ($nota)
        <p class="ml-10 mt-2 text-xs leading-relaxed text-texto/85">{{ $nota }}</p>
    @endif
</{{ $etiqueta }}>
