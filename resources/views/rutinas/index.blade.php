{{--
    Pestaña "Rutinas" (GET /rutinas): las rutinas de la cuenta.

      - Encabezado con cuántas rutinas hay.
      - Tarjeta violeta para armar una rutina con el Coach IA (vista 03).
      - Acceso a la biblioteca de ejercicios.
      - Las rutinas, en el orden en que rotan. La que toca va destacada en azul
        y cada una lleva su número en la rotación.

    $rutinas, $siguiente y $orden los manda RutinaController@index.

    Animación de entrada (resources/css/app.css): cada bloque lleva la clase
    "aparecer" y style="--i: N", su turno en la cascada (N × 45 ms de espera).
    Es suave y corta, la misma de Inicio.
--}}
<x-app-layout titulo="Rutinas">

    {{-- ===================== 1. Encabezado ===================== --}}
    <header class="aparecer" style="--i: 0">
        <p class="text-xs text-tenue">
            {{-- isEmpty() funciona porque $rutinas es una colección de
                 Eloquent, no un arreglo simple. --}}
            @if ($rutinas->isEmpty())
                Tu plan de entrenamiento
            @else
                {{ $rutinas->count() }} {{ $rutinas->count() === 1 ? 'rutina' : 'rutinas' }} · rotan en orden
            @endif
        </p>
        <h1 class="font-display text-2xl font-bold">Rutinas</h1>
    </header>

    {{-- ===================== 2. Armar con el Coach IA ===================== --}}
    {{-- Solo si ya hay rutinas: con la lista vacía, el mismo llamado aparece
         más grande en el estado vacío de abajo. --}}
    @if ($rutinas->isNotEmpty())
        <x-tarjeta variante="ia" class="aparecer mt-4 flex items-center gap-3" style="--i: 1">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-ia/15 text-ia">
                <x-icono nombre="chispas" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold">Arma una rutina con IA</p>
                <p class="mt-0.5 text-xs text-tenue">Según tu objetivo, tu nivel y tu tiempo.</p>
            </div>
            <a href="{{ route('rutinas.generar') }}"
               class="shrink-0 rounded-[10px] bg-primario-fuerte px-3.5 py-2 text-xs font-semibold text-white hover:bg-primario">
                Generar
            </a>
        </x-tarjeta>
    @endif

    {{-- ===================== 3. Biblioteca de ejercicios ===================== --}}
    {{-- <x-tarjeta> con :href se vuelve un enlace completo. --}}
    <x-tarjeta :href="route('ejercicios.index')" class="aparecer mt-2 flex items-center gap-3 px-3.5 py-3" style="--i: 2">
        <x-icono nombre="libro" class="text-primario" />
        <span class="flex-1 text-sm font-semibold">Biblioteca de ejercicios</span>
        <x-icono nombre="flecha-der" class="size-4 text-tenue" />
    </x-tarjeta>

    {{-- ===================== 4. Mis rutinas ===================== --}}
    @if ($rutinas->isNotEmpty())
        <x-titulo-seccion texto="Mis rutinas" class="aparecer mt-6" style="--i: 3" />
    @endif

    {{-- @forelse recorre las rutinas; si no hay ninguna, muestra @empty. --}}
    @forelse ($rutinas as $rutina)
        {{-- ¿Es la que toca? El ?-> evita un error si $siguiente es null
             (cuenta sin rutinas que se puedan empezar). --}}
        @php($esSiguiente = $rutina->id === $siguiente?->id)

        {{-- La que toca usa la variante "destacada" (borde azul). Toda la
             tarjeta lleva al detalle: route('rutinas.show', $rutina) arma
             /rutinas/{id} a partir del modelo. Entran una por una (4, 5,
             6…), con tope en 8 para que una lista larga no tarde más. --}}
        <x-tarjeta :href="route('rutinas.show', $rutina)" :variante="$esSiguiente ? 'destacada' : 'normal'"
                   class="aparecer mt-2 flex items-center gap-3.5 p-3" style="--i: {{ min(4 + $loop->index, 8) }}">
            <div class="relative shrink-0">
                {{-- Portada con franjas y el ícono de la categoría. --}}
                <x-foto-rutina :categoria="$rutina->categoria" class="size-16 rounded-xl" />

                {{-- Número en la rotación (solo rutinas con ejercicios).
                     @isset revisa que exista esa llave en $orden. --}}
                @isset($orden[$rutina->id])
                    <span @class([
                        'absolute -left-1.5 -top-1.5 grid size-[22px] place-items-center rounded-full border text-[11px] font-bold',
                        'border-primario-fuerte bg-primario-fuerte text-white' => $esSiguiente,
                        'border-borde bg-tarjeta text-tenue' => ! $esSiguiente,
                    ])>
                        {{ $orden[$rutina->id] }}
                        <span class="sr-only">en la rotación</span>
                    </span>
                @endisset
            </div>

            <div class="min-w-0 flex-1">
                {{-- Etiqueta pequeña: "Fuerza · Toca ahora", sin partes vacías. --}}
                @php($etiqueta = collect([$rutina->categoria ?: null, $esSiguiente ? 'Toca ahora' : null])->filter()->join(' · '))
                @if ($etiqueta !== '')
                    <p class="etiqueta text-primario">{{ $etiqueta }}</p>
                @endif
                <p class="mt-0.5 truncate font-bold">{{ $rutina->nombre }}</p>
                {{-- "5 ejercicios · 55 min · Intermedio". --}}
                <p class="mt-0.5 text-xs text-tenue">{{ $rutina->resumen }}</p>

                {{-- Hasta 3 grupos musculares como chips, y "+N" si hay más. --}}
                @if ($rutina->grupos)
                    <ul class="mt-2 flex flex-wrap gap-1.5">
                        @foreach (array_slice($rutina->grupos, 0, 3) as $grupo)
                            <li class="rounded-full border border-borde px-2 py-0.5 text-[11px] text-tenue">{{ $grupo }}</li>
                        @endforeach
                        @if (count($rutina->grupos) > 3)
                            <li class="rounded-full border border-borde px-2 py-0.5 text-[11px] text-tenue">+{{ count($rutina->grupos) - 3 }}</li>
                        @endif
                    </ul>
                @endif
            </div>
            <x-icono nombre="flecha-der" class="text-tenue" />
        </x-tarjeta>
    @empty
        {{-- Estado vacío: una lista vacía no es un error, pero hay que
             explicar qué hacer en vez de dejar la pantalla en blanco. --}}
        <div class="aparecer mt-14 flex flex-col items-center px-4 text-center" style="--i: 1">
            <span class="grid size-[72px] place-items-center rounded-full bg-ia/15 text-ia">
                <x-icono nombre="chispas" class="size-8" />
            </span>
            <p class="mt-4 font-bold">Aún no tienes rutinas</p>
            <p class="mt-1.5 text-sm text-tenue">El Coach IA te arma una según tu objetivo, tu nivel y el tiempo que tienes.</p>
            <a href="{{ route('rutinas.generar') }}" class="mt-5 inline-flex items-center gap-1.5 rounded-xl bg-primario-fuerte px-4 py-2.5 text-sm font-semibold text-white hover:bg-primario">
                <x-icono nombre="chispas" class="size-4" /> Arma tu rutina con IA
            </a>
        </div>
    @endforelse
</x-app-layout>
