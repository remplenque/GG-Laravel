{{--
    Detalle de una rutina (GET /rutinas/{rutina}), con el diseño de la vista 04
    del mockup ("Rutina generada"):

      - arriba, el botón para volver y una etiqueta con la categoría y el nivel;
      - el título grande y el resumen (ejercicios · minutos · nivel);
      - pestañas para filtrar por grupo muscular (en el mockup son los días);
      - los ejercicios numerados, cada uno con su ficha;
      - abajo, fijos, los botones "Coach IA" y "Empezar rutina".

    $rutina la manda RutinaController@show, ya con sus ejercicios cargados.

    Animación de entrada (resources/css/app.css): cada bloque lleva la clase
    "aparecer" y style="--i: N", su turno en la cascada (N × 45 ms de espera),
    la misma suave de Inicio. Los botones fijos de abajo no se animan: tienen
    que estar listos para tocarse apenas se abre la pantalla.
--}}

{{-- :navegacion="false" esconde la barra inferior: es una pantalla interna y
     en su lugar van los botones fijos del slot "pie" (al final del archivo). --}}
<x-app-layout :titulo="$rutina->nombre" :navegacion="false">

    {{-- x-data crea el estado de Alpine.js para esta pantalla: "grupo" guarda
         la pestaña elegida (null = Todos). Se cambia en el navegador, sin
         volver a pedir la página al servidor. --}}
    <div x-data="{ grupo: null }">

        {{-- ===================== 1. Volver + etiqueta ===================== --}}
        <div class="aparecer flex items-center gap-3" style="--i: 0">
            {{-- route('rutinas.index') arma la URL de la lista por su nombre. --}}
            <a href="{{ route('rutinas.index') }}"
               class="grid size-10 shrink-0 place-items-center rounded-xl border border-borde bg-tarjeta text-texto hover:border-primario/60"
               aria-label="Volver a mis rutinas">
                <x-icono nombre="flecha-izq" />
            </a>

            {{-- collect([...])->filter()->join(' · ') junta solo las partes que
                 existen: "Rutina · Fuerza · Intermedio" o solo "Rutina". --}}
            <span class="inline-flex items-center gap-1.5 rounded-full border border-borde bg-tarjeta px-3 py-1 text-[11px] font-semibold text-tenue">
                <x-icono nombre="lista" class="size-3.5 text-primario" />
                {{ collect(['Rutina', $rutina->categoria ?: null, $rutina->nivel ?: null])->filter()->join(' · ') }}
            </span>
        </div>

        {{-- ===================== 2. Título y resumen ===================== --}}
        {{-- titulo y resumen son atributos calculados del modelo Rutina
             ("Push A · Pecho y hombro" y "5 ejercicios · 55 min · Intermedio"). --}}
        {{-- El título y su resumen entran juntos, en el mismo turno. --}}
        <div class="aparecer" style="--i: 1">
            <h1 class="mt-4 font-display text-2xl font-bold leading-tight">{{ $rutina->titulo }}</h1>
            <p class="mt-1 text-xs text-tenue">{{ $rutina->resumen }}</p>
        </div>

        {{-- ===================== 3. Pestañas por grupo muscular ===================== --}}
        {{-- Solo tienen sentido si la rutina trabaja más de un grupo. --}}
        @if (count($rutina->grupos) > 1)
            {{-- -mx-4 px-4 + overflow-x-auto: si no caben, se deslizan de lado
                 de borde a borde de la pantalla, como en un celular. --}}
            <div class="aparecer -mx-4 mt-4 flex gap-2 overflow-x-auto px-4 pb-1" style="--i: 2"
                 role="tablist" aria-label="Filtrar por grupo muscular">
                {{-- "Todos" vuelve a mostrar la rutina completa. :class y
                     :aria-selected (con dos puntos) los calcula Alpine según
                     el valor actual de "grupo". --}}
                <button type="button" role="tab" @click="grupo = null"
                        :aria-selected="grupo === null"
                        :class="grupo === null ? 'border-primario-fuerte bg-primario-fuerte text-white' : 'border-borde bg-tarjeta text-tenue hover:text-texto'"
                        class="shrink-0 rounded-[10px] border px-4 py-2 text-xs font-semibold transition">
                    Todos
                </button>

                @foreach ($rutina->grupos as $grupo)
                    {{-- @js convierte el texto de PHP en un valor de JavaScript
                         con las comillas bien puestas ('Pecho'). --}}
                    <button type="button" role="tab" @click="grupo = @js($grupo)"
                            :aria-selected="grupo === @js($grupo)"
                            :class="grupo === @js($grupo) ? 'border-primario-fuerte bg-primario-fuerte text-white' : 'border-borde bg-tarjeta text-tenue hover:text-texto'"
                            class="shrink-0 whitespace-nowrap rounded-[10px] border px-4 py-2 text-xs font-semibold transition">
                        {{ $grupo }}
                    </button>
                @endforeach
            </div>
        @endif

        {{-- ===================== 4. Ejercicios ===================== --}}
        <h2 class="sr-only">Ejercicios</h2>
        {{-- La lista entra completa, como un solo bloque. Si cada fila se
             animara por separado, al cambiar de pestaña (x-show) las filas
             volverían a animarse cada vez, y eso sería demasiado movimiento. --}}
        <ol class="aparecer mt-4 space-y-2" style="--i: 3">
            {{-- $i es la llave de la colección (0, 1, 2…): el número visible
                 es $i + 1, y no cambia aunque se filtre por grupo. --}}
            @forelse ($rutina->ejercicios as $i => $item)
                {{-- @php(...) declara una variable corta para no repetir
                     $item->ejercicio en cada línea. --}}
                @php($ejercicio = $item->ejercicio)

                {{-- x-show esconde la fila si hay un grupo elegido y este
                     ejercicio no es de ese grupo. --}}
                <li x-show="grupo === null || grupo === @js($ejercicio->grupo)">
                    {{-- Componente propio: resources/views/components/ejercicio-rutina.blade.php.
                         El detalle junta "4 × 8 · 60 kg" con "descanso 2 min".
                         Toda la tarjeta lleva a la ficha del ejercicio (vista 06). --}}
                    <x-ejercicio-rutina
                        :numero="$i + 1"
                        :nombre="$ejercicio->nombre"
                        :detalle="collect([$item->resumen, $item->descanso])->filter()->join(' · ')"
                        :lateral="$ejercicio->grupo"
                        :nota="$item->nota"
                        :href="route('ejercicios.show', $ejercicio)" />
                </li>
            @empty
                <li class="py-8 text-center text-sm text-tenue">Esta rutina aún no tiene ejercicios.</li>
            @endforelse
        </ol>

        {{-- ===================== 5. Pregúntale al coach ===================== --}}
        {{-- Tarjeta violeta (lo de la IA va en violeta), en el mismo lugar que
             "Por qué esta rutina" en el mockup. --}}
        @if ($rutina->ejercicios->isNotEmpty())
            <x-tarjeta variante="ia" class="aparecer mt-4" style="--i: 4">
                <p class="etiqueta flex items-center gap-1.5 text-ia">
                    <x-icono nombre="chispas" class="size-4" /> ¿Dudas con esta rutina?
                </p>
                <p class="mt-2 text-sm leading-relaxed">
                    Pregúntale al coach cómo se hace un ejercicio, cuánto descansar o con qué reemplazarlo si te falta una máquina.
                </p>
            </x-tarjeta>
        @endif
    </div>

    {{-- ===================== 6. Botones fijos abajo ===================== --}}
    {{-- <x-slot:pie> rellena el hueco "pie" del layout (app-layout), que lo
         deja fijo al fondo de la pantalla. Solo si se puede empezar. --}}
    @if ($rutina->ejercicios->isNotEmpty())
        <x-slot:pie>
            {{-- Dos columnas: la primera mide lo que su contenido (auto) y la
                 segunda toma el resto (1fr), como en el mockup. --}}
            <div class="grid grid-cols-[auto_1fr] gap-2">
                <a href="{{ route('coach') }}"
                   class="flex h-12 items-center justify-center gap-1.5 rounded-2xl border border-ia-borde bg-tarjeta px-4 text-sm font-semibold text-texto hover:bg-white/5">
                    <x-icono nombre="chispas" class="size-4 text-ia" /> Coach IA
                </a>
                {{-- Abre "Registrar entrenamiento" con esta rutina (?rutina=ID). --}}
                <a href="{{ route('entrenamientos.create', ['rutina' => $rutina->id]) }}"
                   class="flex h-12 items-center justify-center gap-2 rounded-2xl bg-primario-fuerte text-sm font-bold text-white hover:bg-primario">
                    <x-icono nombre="play" class="size-4" /> Empezar rutina
                </a>
            </div>
            {{-- Aviso visible: la app orienta, no reemplaza a un profesional
                 (riesgo "consejos incorrectos" de la propuesta). --}}
            <p class="mt-2 text-center text-[11px] text-tenue">
                Orientación general. Ante dolor o lesión, consulta a un profesional.
            </p>
        </x-slot:pie>
    @endif
</x-app-layout>
