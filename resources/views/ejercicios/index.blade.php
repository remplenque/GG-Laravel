{{--
    Biblioteca de ejercicios (GET /ejercicios): el catálogo RepDB.

      1. Encabezado con cuántos ejercicios se encontraron.
      2. Buscador por nombre.
      3. Chips para filtrar por grupo muscular.
      4. La lista de ejercicios; cada uno lleva a su detalle (vista 06).
      5. Paginación: anterior / siguiente.

    $ejercicios, $grupos, $q y $grupo los manda EjercicioController@index.
--}}
<x-app-layout titulo="Biblioteca de ejercicios">

    {{-- ===================== 1. Encabezado ===================== --}}
    {{-- total() es la cantidad de ejercicios que cumplen el filtro (no solo
         los de esta página). El botón atrás vuelve a Rutinas, desde donde se
         entra a la biblioteca. --}}
    <x-encabezado titulo="Ejercicios" etiqueta="Biblioteca de ejercicios"
                  :subtitulo="$ejercicios->total().' '.($ejercicios->total() === 1 ? 'ejercicio' : 'ejercicios')"
                  :atras="route('rutinas.index')" class="aparecer" style="--i: 0" />

    {{-- ===================== 2. Buscador ===================== --}}
    {{-- Formulario GET: al enviarlo, el navegador va a /ejercicios?q=texto.
         No necesita @csrf porque solo lee datos, no guarda nada. --}}
    <form method="GET" action="{{ route('ejercicios.index') }}" class="aparecer relative mt-4" style="--i: 1">
        {{-- Lupa dibujada dentro del campo, a la izquierda. --}}
        <x-icono nombre="buscar" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-tenue" />

        {{-- value="{{ $q }}" deja escrito lo que se buscó al recargar la página. --}}
        <input type="search" name="q" value="{{ $q }}" placeholder="Buscar ejercicio…"
               class="w-full rounded-xl border-borde bg-tarjeta py-2.5 pl-9 pr-3 text-sm text-texto placeholder:text-tenue focus:border-primario focus:ring-primario">

        {{-- Si hay un grupo elegido, se manda escondido para no perderlo al buscar. --}}
        @if ($grupo)
            <input type="hidden" name="grupo" value="{{ $grupo }}">
        @endif
    </form>

    {{-- ===================== 3. Chips de grupo muscular ===================== --}}
    {{-- overflow-x-auto: si los chips no caben, se deslizan de lado en vez de
         bajar a otra línea. -mx-4 px-4 los deja llegar hasta el borde de la pantalla. --}}
    <nav class="aparecer -mx-4 mt-3 flex gap-2 overflow-x-auto px-4 pb-1" style="--i: 2" aria-label="Filtrar por grupo muscular">
        {{-- "Todos" quita el filtro de grupo pero mantiene la búsqueda.
             array_filter saca los valores vacíos para no dejar "?q=" sin nada en la URL. --}}
        <a href="{{ route('ejercicios.index', array_filter(['q' => $q])) }}"
           @class([
               'shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium',
               'border-primario bg-primario/15 text-primario' => ! $grupo,
               'border-borde text-tenue hover:text-texto' => $grupo,
           ])>
            Todos
        </a>

        {{-- Un chip por grupo. El que coincide con $grupo se marca en azul. --}}
        @foreach ($grupos as $opcion)
            <a href="{{ route('ejercicios.index', array_filter(['q' => $q, 'grupo' => $opcion])) }}"
               @class([
                   'shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium',
                   'border-primario bg-primario/15 text-primario' => $opcion === $grupo,
                   'border-borde text-tenue hover:text-texto' => $opcion !== $grupo,
               ])>
                {{ $opcion }}
            </a>
        @endforeach
    </nav>

    {{-- ===================== 4. Lista de ejercicios ===================== --}}
    {{-- @forelse recorre los ejercicios de esta página; si no hay, muestra @empty. --}}
    @forelse ($ejercicios as $ejercicio)
        {{-- Toda la tarjeta es un enlace al detalle. Entran en cascada (3, 4,
             5…) con tope en 8, igual que en Rutinas. --}}
        <x-tarjeta :href="route('ejercicios.show', $ejercicio)"
                   class="aparecer mt-2 flex items-center gap-3 p-2.5" style="--i: {{ min(3 + $loop->index, 8) }}">

            {{-- Miniatura del ejercicio. url_imagen viene del modelo Ejercicio
                 (arma la URL de jsDelivr). loading="lazy" la descarga recién
                 cuando aparece en pantalla. Si no tiene imagen, va un ícono. --}}
            @if ($ejercicio->url_imagen)
                <img src="{{ $ejercicio->url_imagen }}" alt="" loading="lazy"
                     class="size-14 shrink-0 rounded-xl bg-white object-contain">
            @else
                <span class="grid size-14 shrink-0 place-items-center rounded-xl bg-tarjeta-alta text-tenue">
                    <x-icono nombre="imagen" />
                </span>
            @endif

            {{-- Nombre, y debajo "Grupo · Equipo". truncate corta con "…" los nombres largos. --}}
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">{{ $ejercicio->nombre }}</p>
                <p class="mt-0.5 truncate text-xs text-tenue">{{ $ejercicio->grupo }} · {{ $ejercicio->equipo }}</p>
            </div>

            <x-icono nombre="flecha-der" class="size-4 text-tenue" />
        </x-tarjeta>
    @empty
        {{-- Estado vacío: la búsqueda no encontró nada. --}}
        <div class="aparecer mt-14 flex flex-col items-center px-4 text-center" style="--i: 3">
            <span class="grid size-16 place-items-center rounded-full bg-primario/12 text-primario">
                <x-icono nombre="buscar" class="size-7" />
            </span>
            <p class="mt-4 font-bold">No encontramos ejercicios</p>
            <p class="mt-1 text-sm text-tenue">Prueba con otra palabra o quita el filtro de grupo.</p>
            <a href="{{ route('ejercicios.index') }}" class="mt-4 text-sm font-medium text-primario hover:underline">Ver todos</a>
        </div>
    @endforelse

    {{-- ===================== 5. Paginación ===================== --}}
    {{-- hasPages() es verdadero solo si hay más de una página.
         previousPageUrl() / nextPageUrl() dan null en la primera / última
         página; en ese caso el botón se muestra apagado. --}}
    @if ($ejercicios->hasPages())
        <nav class="mt-4 flex items-center justify-between gap-3" aria-label="Páginas">
            @if ($ejercicios->previousPageUrl())
                <a href="{{ $ejercicios->previousPageUrl() }}" class="flex items-center gap-1 rounded-xl border border-borde bg-tarjeta px-3 py-2 text-xs font-medium hover:border-primario/60">
                    <x-icono nombre="flecha-izq" class="size-4" /> Anterior
                </a>
            @else
                <span class="flex items-center gap-1 rounded-xl border border-borde px-3 py-2 text-xs text-tenue/50">
                    <x-icono nombre="flecha-izq" class="size-4" /> Anterior
                </span>
            @endif

            <span class="text-xs text-tenue">Página {{ $ejercicios->currentPage() }} de {{ $ejercicios->lastPage() }}</span>

            @if ($ejercicios->nextPageUrl())
                <a href="{{ $ejercicios->nextPageUrl() }}" class="flex items-center gap-1 rounded-xl border border-borde bg-tarjeta px-3 py-2 text-xs font-medium hover:border-primario/60">
                    Siguiente <x-icono nombre="flecha-der" class="size-4" />
                </a>
            @else
                <span class="flex items-center gap-1 rounded-xl border border-borde px-3 py-2 text-xs text-tenue/50">
                    Siguiente <x-icono nombre="flecha-der" class="size-4" />
                </span>
            @endif
        </nav>
    @endif
</x-app-layout>
