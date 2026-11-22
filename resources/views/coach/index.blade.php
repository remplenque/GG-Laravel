{{--
    Vista 02 · Coach IA: chat sobre ejercicios, técnica, descansos y rutinas.

    Partes, de arriba hacia abajo (como en el mockup):
      1. Encabezado fijo: ícono violeta, título y "+" para empezar una nueva conversación.
      2. Conversación vacía: saludo del coach, preguntas de ejemplo y aviso de privacidad.
      3. Mensajes (parcial coach/_mensaje): los del usuario a la derecha en
         azul; los del coach a la izquierda, con la etiqueta violeta.
      4. Respuestas rápidas bajo el último mensaje.
      5. Campo para preguntar, fijo sobre la barra de navegación, con el aviso de salud.

    Variables (CoachController@index): $mensajes, $ejercicios (id → nombre),
    $sugerencias, $pregunta (texto precargado) y $maxPregunta.

    Alpine (resources/js/modulos/coach.js) envía las preguntas sin recargar
    la página. Sin JavaScript el chat funciona igual (formularios normales).
--}}
<x-app-layout titulo="Coach IA">
    <div x-data="coach({ hayMensajes: @js(count($mensajes) > 0) })" @submit="enviar($event)">
        {{-- ===================== 1. Encabezado ===================== --}}
        <header class="sticky top-0 z-10 -mx-4 -mt-4 flex items-center gap-3 border-b border-borde/80 bg-fondo/80 px-4 py-3 backdrop-blur-xl lg:-mx-8 lg:-mt-8 lg:px-8 lg:py-4">
            <span class="orbe-ia grid size-10 shrink-0 place-items-center rounded-xl text-white">
                <x-icono nombre="chispas" class="size-5" />
            </span>
            <div class="min-w-0 flex-1">
                <h1 class="truncate font-display text-xl font-bold">Coach IA</h1>
                <p class="flex items-center gap-1.5 truncate text-xs text-tenue">
                    <span class="size-1.5 rounded-full bg-exito shadow-[0_0_6px_var(--color-exito)]"></span>
                    Dudas de ejercicios, técnica y rutinas
                </p>
            </div>
            {{-- DELETE coach → CoachController@nueva: borra la conversación. --}}
            <form method="POST" action="{{ route('coach.nueva') }}" x-show="hayMensajes" @if (! $mensajes) x-cloak @endif>
                @csrf
                @method('DELETE')
                <button type="submit" title="Nueva conversación" aria-label="Nueva conversación"
                        class="presionable grid size-10 place-items-center rounded-xl border border-borde bg-tarjeta text-texto transition hover:border-ia/60 hover:text-ia">
                    <x-icono nombre="mas" />
                </button>
            </form>
        </header>

        <x-aviso />

        {{-- ===================== 2. Conversación vacía ===================== --}}
        <section x-show="! hayMensajes" @if ($mensajes) x-cloak @endif>
            <x-tarjeta variante="ia" class="aparecer mt-5 p-5" style="--i: 1">
                <span class="orbe-ia grid size-12 place-items-center rounded-2xl text-white animate-flotar">
                    <x-icono nombre="chispas" class="size-6" />
                </span>
                <p class="mt-4 font-display text-xl font-bold leading-tight">
                    Hola, {{ auth()->user()->name }}. ¿En qué te ayudo hoy?
                </p>
                <p class="mt-1.5 text-sm leading-relaxed text-tenue">
                    Pregúntame por la técnica de un ejercicio, cuánto descansar, cuándo subir el peso o con qué
                    reemplazar una máquina.
                </p>
            </x-tarjeta>

            <x-titulo-seccion texto="Prueba con" class="aparecer mt-6" style="--i: 2" />
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                @foreach ($sugerencias as $sugerencia)
                    <form method="POST" action="{{ route('coach.preguntar') }}" data-pregunta
                          class="aparecer" style="--i: {{ 3 + $loop->index }}">
                        @csrf
                        <input type="hidden" name="pregunta" value="{{ $sugerencia }}">
                        <button type="submit" :disabled="enviando"
                                class="tarjeta group flex w-full items-center gap-2 rounded-2xl border border-borde bg-tarjeta/85 px-3.5 py-3 text-left text-sm text-texto transition hover:border-ia/50 disabled:opacity-50">
                            <span class="flex-1">{{ $sugerencia }}</span>
                            <x-icono nombre="enviar" class="size-4 text-ia opacity-50 transition group-hover:translate-x-0.5 group-hover:opacity-100" />
                        </button>
                    </form>
                @endforeach
            </div>

            {{-- Aviso de privacidad (riesgo legal de la propuesta). --}}
            <p class="aparecer mt-6 flex items-start gap-2 text-xs leading-relaxed text-tenue" style="--i: 7">
                <x-icono nombre="candado" class="mt-px size-4 text-tenue" />
                Tus preguntas se envían a un servicio de IA solo con tu nivel y objetivo, nunca con tu nombre ni tu
                correo. Puedes borrar la conversación cuando quieras con el botón +.
            </p>
        </section>

        {{-- ===================== 3. Mensajes ===================== --}}
        <ol x-ref="lista" class="mt-4 space-y-3" aria-label="Conversación" aria-live="polite">
            @foreach ($mensajes as $mensaje)
                {{-- Solo los dos últimos entran con animación. --}}
                @include('coach._mensaje', ['nuevo' => $loop->remaining < 2])
            @endforeach
        </ol>

        {{-- Mientras se envía: la pregunta aparece al tiro como burbuja y el
             coach queda "escribiendo…" hasta que llega la respuesta. --}}
        <ol x-cloak x-show="enviando" class="mt-3 space-y-3">
            <li class="flex justify-end">
                <p class="max-w-[85%] whitespace-pre-line rounded-2xl rounded-br-md bg-primario-fuerte/80 px-3.5 py-2.5 text-sm leading-relaxed text-white" x-text="enviado"></p>
            </li>
            <li class="flex gap-2">
                <span class="orbe-ia mt-1 grid size-7 shrink-0 place-items-center rounded-lg text-white" aria-hidden="true">
                    <x-icono nombre="chispas" class="size-3.5" />
                </span>
                <div class="w-56 rounded-2xl rounded-tl-md border border-borde bg-tarjeta px-3.5 py-3">
                    <p class="etiqueta text-ia">Coach IA</p>
                    <p class="escribiendo mt-2 flex gap-1" role="status" aria-label="El coach está escribiendo">
                        <span class="size-1.5 rounded-full bg-ia"></span>
                        <span class="size-1.5 rounded-full bg-ia"></span>
                        <span class="size-1.5 rounded-full bg-ia"></span>
                    </p>
                    <span class="esqueleto mt-3 block h-2 w-full rounded-full"></span>
                    <span class="esqueleto mt-1.5 block h-2 w-3/4 rounded-full"></span>
                </div>
            </li>
        </ol>

        {{-- ===================== 4. Respuestas rápidas ===================== --}}
        <div x-show="hayMensajes && ! enviando" @if (! $mensajes) x-cloak @endif
             class="sin-scrollbar -mx-4 mt-3 flex gap-2 overflow-x-auto px-4 pb-1 lg:-mx-8 lg:px-8">
            @foreach ($sugerencias as $sugerencia)
                <form method="POST" action="{{ route('coach.preguntar') }}" data-pregunta class="shrink-0">
                    @csrf
                    <input type="hidden" name="pregunta" value="{{ $sugerencia }}">
                    <button type="submit"
                            class="presionable whitespace-nowrap rounded-full border border-borde bg-tarjeta px-3 py-1.5 text-xs font-medium text-texto transition hover:border-ia-borde hover:bg-white/5">
                        {{ $sugerencia }}
                    </button>
                </form>
            @endforeach
        </div>

        {{-- Espacio para que el último mensaje no quede tapado por el campo fijo. --}}
        <div class="h-28" aria-hidden="true"></div>

        {{-- ===================== 5. Campo para preguntar ===================== --}}
        {{-- En el celular va justo encima de la barra de navegación (h-16 =
             4rem, más el borde seguro); en pantallas grandes, abajo del todo. --}}
        <div class="fixed inset-x-0 bottom-[calc(4rem_+_env(safe-area-inset-bottom))] z-10 border-t border-borde/80 bg-fondo/85 backdrop-blur-xl lg:bottom-0 lg:left-64">
            <div class="mx-auto max-w-md px-4 pt-3 pb-2 lg:max-w-2xl lg:px-8 lg:pb-4">
                <p x-cloak x-show="error" x-text="error" x-transition class="mb-2 rounded-xl border border-error/40 bg-error/10 px-3 py-2 text-xs text-error" role="alert"></p>
                <form method="POST" action="{{ route('coach.preguntar') }}" data-pregunta class="flex items-center gap-2">
                    @csrf
                    <label for="pregunta" class="sr-only">Pregunta para el coach</label>
                    <input id="pregunta" x-ref="campo" name="pregunta" type="text" required maxlength="{{ $maxPregunta }}"
                           value="{{ $pregunta }}" placeholder="Pregunta sobre un ejercicio…" autocomplete="off" enterkeyhint="send"
                           @if ($pregunta !== '') autofocus @endif
                           :readonly="enviando"
                           class="h-12 min-w-0 flex-1 rounded-2xl border-borde bg-tarjeta px-4 text-sm text-texto placeholder:text-tenue transition focus:border-ia focus:ring-ia">
                    <button type="submit" aria-label="Enviar" :disabled="enviando"
                            class="boton-primario boton-ia grid size-12 shrink-0 place-items-center rounded-2xl text-white">
                        <x-icono nombre="enviar" class="size-5" x-show="! enviando" />
                        <span x-cloak x-show="enviando" class="size-5 rounded-full border-2 border-white/30 border-t-white animate-girar"></span>
                    </button>
                </form>
                <x-input-error :messages="$errors->get('pregunta')" class="mt-1.5" />
                <p class="mt-1.5 text-center text-[11px] text-tenue">
                    Orientación general: no reemplaza a un profesional de la salud.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
