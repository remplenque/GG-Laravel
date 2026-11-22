{{--
    Biblioteca de ejercicios (GET /ejercicios): el catálogo RepDB.

      1. Encabezado.
      2. Buscador por nombre (en vivo con JavaScript: resources/js/modulos/busqueda.js).
      3. Chips para filtrar por grupo muscular, y el acceso a "Explorar en wger".
      4. La lista de ejercicios; cada uno lleva a su detalle (vista 06). Al
         pasar el mouse, la miniatura muestra el final del movimiento.
      5. Paginación.

    La búsqueda en vivo reemplaza solo lo que tiene id (total-resultados,
    chips-grupo y resultados), así el campo no pierde el foco al escribir.

    $ejercicios, $grupos, $q y $grupo los manda EjercicioController@index.
--}}
<x-app-layout titulo="Biblioteca de ejercicios">

    {{-- ===================== 1. Encabezado ===================== --}}
    <x-encabezado titulo="Ejercicios" etiqueta="Biblioteca de ejercicios"
                  :atras="route('rutinas.index')" class="aparecer" style="--i: 0" />

    <div x-data="busqueda">
        {{-- ===================== 2. Buscador ===================== --}}
        <form x-ref="form" method="GET" action="{{ route('ejercicios.index') }}" class="aparecer relative mt-4" style="--i: 1"
              @submit.prevent="cargar()">
            <x-icono nombre="buscar" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-tenue" />

            <input type="search" name="q" value="{{ $q }}" placeholder="Buscar ejercicio…" autocomplete="off" enterkeyhint="search"
                   aria-label="Buscar ejercicio" @input="buscar()"
                   class="h-12 w-full rounded-2xl border-borde bg-tarjeta/85 pl-10 pr-10 text-sm text-texto placeholder:text-tenue transition focus:border-primario focus:ring-primario">

            <span x-cloak x-show="cargando" class="absolute right-3.5 top-1/2 size-4 -translate-y-1/2 rounded-full border-2 border-primario/30 border-t-primario animate-girar" aria-hidden="true"></span>

            @if ($grupo)
                <input type="hidden" name="grupo" value="{{ $grupo }}">
            @endif
        </form>

        {{-- ===================== 3. Chips de grupo muscular ===================== --}}
        <nav id="chips-grupo" class="aparecer sin-scrollbar -mx-4 mt-3 flex gap-2 overflow-x-auto px-4 pb-1 lg:-mx-8 lg:px-8" style="--i: 2" aria-label="Filtrar por grupo muscular">
            <a href="{{ route('ejercicios.index', array_filter(['q' => $q])) }}"
               @class([
                   'presionable shrink-0 rounded-full border px-3.5 py-1.5 text-xs font-medium transition',
                   'border-primario bg-primario/15 text-primario' => ! $grupo,
                   'border-borde text-tenue hover:border-primario/40 hover:text-texto' => $grupo,
               ])>
                Todos
            </a>
            @foreach ($grupos as $opcion)
                <a href="{{ route('ejercicios.index', array_filter(['q' => $q, 'grupo' => $opcion])) }}"
                   @class([
                       'presionable shrink-0 rounded-full border px-3.5 py-1.5 text-xs font-medium transition',
                       'border-primario bg-primario/15 text-primario' => $opcion === $grupo,
                       'border-borde text-tenue hover:border-primario/40 hover:text-texto' => $opcion !== $grupo,
                   ])>
                    {{ $opcion }}
                </a>
            @endforeach
        </nav>

        {{-- ===================== Explorar en wger ===================== --}}
        <x-tarjeta :href="route('ejercicios.wger')" class="aparecer mt-3 flex items-center gap-3 px-3.5 py-3" style="--i: 2">
            <span class="grid size-9 place-items-center rounded-xl bg-primario/10 text-primario"><x-icono nombre="libro" class="size-4" /></span>
            <span class="flex-1">
                <span class="block text-sm font-semibold">Explorar más en wger</span>
                <span class="block text-xs text-tenue">Catálogo abierto con cientos de ejercicios extra</span>
            </span>
            <x-icono nombre="flecha-der" class="size-4 text-tenue" />
        </x-tarjeta>

        {{-- ===================== 4. Lista de ejercicios ===================== --}}
        <p id="total-resultados" class="mt-5 text-xs text-tenue" aria-live="polite">
            {{ $ejercicios->total() }} {{ $ejercicios->total() === 1 ? 'ejercicio' : 'ejercicios' }}{{ $grupo ? ' de '.$grupo : '' }}{{ $q ? ' para “'.$q.'”' : '' }}
        </p>

        <div id="resultados" :class="cargando && 'opacity-60'" class="transition-opacity lg:grid lg:grid-cols-2 lg:gap-x-2">
            @forelse ($ejercicios as $ejercicio)
                <x-tarjeta :href="route('ejercicios.show', $ejercicio)"
                           class="aparecer group mt-2 flex items-center gap-3 p-2.5" style="--i: {{ min(3 + $loop->index, 8) }}">
                    @if ($ejercicio->url_imagen)
                        <span class="relative size-14 shrink-0 overflow-hidden rounded-xl bg-white">
                            <img src="{{ $ejercicio->url_imagen }}" alt="" loading="lazy" decoding="async"
                                 class="size-full object-contain transition duration-300 group-hover:scale-105">
                            @if ($ejercicio->url_imagen_final)
                                <img src="{{ $ejercicio->url_imagen_final }}" alt="" loading="lazy" decoding="async"
                                     class="absolute inset-0 size-full bg-white object-contain opacity-0 transition duration-300 group-hover:scale-105 group-hover:opacity-100">
                            @endif
                        </span>
                    @else
                        <span class="grid size-14 shrink-0 place-items-center rounded-xl bg-tarjeta-alta text-tenue">
                            <x-icono nombre="imagen" />
                        </span>
                    @endif

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">{{ $ejercicio->nombre }}</p>
                        <p class="mt-0.5 truncate text-xs text-tenue">{{ $ejercicio->grupo }} · {{ $ejercicio->equipo }}</p>
                    </div>

                    <x-icono nombre="flecha-der" class="size-4 text-tenue transition group-hover:translate-x-0.5 group-hover:text-primario" />
                </x-tarjeta>
            @empty
                <div class="aparecer col-span-2 mt-10 flex flex-col items-center px-4 text-center" style="--i: 3">
                    <span class="grid size-16 place-items-center rounded-2xl bg-primario/10 text-primario animate-flotar">
                        <x-icono nombre="buscar" class="size-7" />
                    </span>
                    <p class="mt-4 font-bold">No encontramos ejercicios</p>
                    <p class="mt-1 text-sm text-tenue">Prueba con otra palabra o quita el filtro de grupo.</p>
                    <a href="{{ route('ejercicios.index') }}" class="mt-4 text-sm font-medium text-primario hover:underline">Ver todos</a>
                </div>
            @endforelse

            {{-- ===================== 5. Paginación ===================== --}}
            <x-paginacion :paginas="$ejercicios" class="col-span-2 mt-4" />
        </div>
    </div>
</x-app-layout>
