{{--
    Explorar en wger (GET /ejercicios/wger): ejercicios de la API pública de
    wger, como la pestaña wger de la versión Flutter.

      1. Encabezado y nota de dónde salen los datos.
      2. Chips por categoría (?categoria=Pecho).
      3. La lista: imagen, nombre, categoría, músculos y equipo. Al tocar una
         tarjeta se despliega la descripción (<details>, sin JavaScript).
      4. Aviso si wger no respondió.

    $ejercicios, $categorias, $categoria y $error los manda EjercicioController@wger.
    Cada ejercicio es un arreglo armado por App\Support\Wger.
--}}
<x-app-layout titulo="Explorar en wger">

    {{-- ===================== 1. Encabezado ===================== --}}
    <x-encabezado titulo="Explorar en wger" etiqueta="Biblioteca de ejercicios"
                  :subtitulo="$ejercicios->count().' '.($ejercicios->count() === 1 ? 'ejercicio' : 'ejercicios')"
                  :atras="route('ejercicios.index')" class="aparecer" style="--i: 0" />

    <p class="aparecer mt-3 text-xs text-tenue" style="--i: 1">
        Datos de la API abierta de <a href="https://wger.de" class="text-primario hover:underline" target="_blank" rel="noopener">wger.de</a>.
        Si un ejercicio no está traducido al español, se muestra en inglés.
    </p>

    @if ($error)
        {{-- ===================== 4. wger no respondió ===================== --}}
        <x-tarjeta class="aparecer mt-6 flex flex-col items-center px-6 py-8 text-center" style="--i: 2">
            <x-icono nombre="herramienta" class="size-7 text-tenue" />
            <p class="mt-3 font-bold">No pudimos conectar con wger</p>
            <p class="mt-1 text-sm text-tenue">Intenta de nuevo en un rato. La biblioteca principal sigue disponible.</p>
            <a href="{{ route('ejercicios.wger') }}" class="mt-4 text-sm font-semibold text-primario hover:underline">Reintentar</a>
        </x-tarjeta>
    @else
        {{-- ===================== 2. Chips por categoría ===================== --}}
        <nav class="aparecer sin-scrollbar -mx-4 mt-3 flex gap-2 overflow-x-auto px-4 pb-1 lg:-mx-8 lg:px-8" style="--i: 2" aria-label="Filtrar por categoría">
            <a href="{{ route('ejercicios.wger') }}"
               @class([
                   'shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium',
                   'border-primario bg-primario/15 text-primario' => ! $categoria,
                   'border-borde text-tenue hover:text-texto' => $categoria,
               ])>
                Todas
            </a>
            @foreach ($categorias as $opcion)
                <a href="{{ route('ejercicios.wger', ['categoria' => $opcion]) }}"
                   @class([
                       'shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium',
                       'border-primario bg-primario/15 text-primario' => $opcion === $categoria,
                       'border-borde text-tenue hover:text-texto' => $opcion !== $categoria,
                   ])>
                    {{ $opcion }}
                </a>
            @endforeach
        </nav>

        {{-- ===================== 3. Lista ===================== --}}
        <ul class="aparecer mt-4 space-y-2" style="--i: 3">
            @forelse ($ejercicios as $ejercicio)
                <li>
                    {{-- <details>/<summary>: el navegador abre y cierra la
                         descripción solo al tocar el resumen. --}}
                    <details class="tarjeta group rounded-2xl border border-borde bg-tarjeta/85 p-3 open:border-primario/40">
                        <summary class="flex cursor-pointer list-none items-center gap-3">
                            @if ($ejercicio['imagen'])
                                <img src="{{ $ejercicio['imagen'] }}" alt="" loading="lazy"
                                     class="size-14 shrink-0 rounded-xl bg-white object-contain">
                            @else
                                <span class="grid size-14 shrink-0 place-items-center rounded-xl bg-fondo text-tenue">
                                    <x-icono nombre="pesa" />
                                </span>
                            @endif

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold">{{ $ejercicio['nombre'] }}</p>
                                {{-- La categoría y el equipo, sin las partes que falten. --}}
                                <p class="mt-0.5 truncate text-xs text-tenue">
                                    {{ collect([$ejercicio['categoria'], ...$ejercicio['equipamiento']])->filter()->join(' · ') }}
                                </p>
                                @unless ($ejercicio['en_espanol'])
                                    <span class="mt-1 inline-block rounded-full border border-borde px-2 py-0.5 text-[10px] text-tenue">En inglés</span>
                                @endunless
                            </div>

                            {{-- La flecha gira cuando la tarjeta está abierta (group-open:). --}}
                            <x-icono nombre="flecha-der" class="size-4 shrink-0 text-tenue transition group-open:rotate-90" />
                        </summary>

                        <div class="mt-3 border-t border-borde pt-3 text-sm">
                            @if ($ejercicio['musculos'])
                                <div class="mb-2 flex flex-wrap gap-1.5">
                                    @foreach ($ejercicio['musculos'] as $musculo)
                                        <span class="rounded-full border border-primario/60 bg-primario/12 px-2.5 py-1 text-[11px] font-medium text-primario">{{ $musculo }}</span>
                                    @endforeach
                                </div>
                            @endif
                            {{-- whitespace-pre-line respeta los saltos de línea del texto. --}}
                            <p class="whitespace-pre-line leading-relaxed text-tenue">{{ $ejercicio['descripcion'] ?: 'Sin descripción.' }}</p>
                        </div>
                    </details>
                </li>
            @empty
                <li class="py-8 text-center text-sm text-tenue">No hay ejercicios en esta categoría.</li>
            @endforelse
        </ul>
    @endif
</x-app-layout>
