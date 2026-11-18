{{--
    Vista 02 · Coach IA: chat sobre ejercicios, técnica, descansos y rutinas.

    Partes, de arriba hacia abajo (como en el mockup):
      1. Encabezado fijo: ícono violeta, título y "+" para empezar una nueva conversación.
      2. Conversación vacía: saludo del coach, preguntas de ejemplo y aviso de privacidad.
      3. Mensajes: los del usuario a la derecha en azul; los del coach a la
         izquierda, en tarjeta con la etiqueta violeta "Coach IA".
      4. Respuestas rápidas bajo el último mensaje.
      5. Campo para preguntar, fijo sobre la barra de navegación, con el aviso de salud.

    Variables (CoachController@index): $mensajes, $ejercicios (id → nombre),
    $sugerencias, $pregunta (texto precargado) y $maxPregunta.

    Alpine (x-data) solo agrega la espera: al enviar, la pregunta aparece al
    tiro como burbuja y el coach muestra "escribiendo…" hasta que llega la
    respuesta. Sin JavaScript el chat funciona igual (formularios normales).
--}}
<x-app-layout titulo="Coach IA">
    {{-- ===================== 1. Encabezado ===================== --}}
    {{-- sticky: queda pegado arriba al hacer scroll por la conversación.
         -mx-4 -mt-4 lo estira hasta los bordes, por encima del padding de <main>. --}}
    <header class="sticky top-0 z-10 -mx-4 -mt-4 flex items-center gap-3 border-b border-borde bg-fondo/95 px-4 py-3 backdrop-blur">
        <span class="grid size-10 shrink-0 place-items-center rounded-xl border border-ia-borde bg-ia-fondo text-ia">
            <x-icono nombre="chispas" class="size-5" />
        </span>
        <div class="min-w-0 flex-1">
            <h1 class="truncate font-display text-xl font-bold">Coach IA</h1>
            <p class="truncate text-xs text-tenue">Dudas de ejercicios, técnica y rutinas</p>
        </div>
        @if ($mensajes)
            {{-- DELETE coach → CoachController@nueva: borra la conversación. --}}
            <form method="POST" action="{{ route('coach.nueva') }}">
                @csrf
                @method('DELETE')
                <button type="submit" title="Nueva conversación" aria-label="Nueva conversación"
                        class="grid size-10 place-items-center rounded-xl border border-borde bg-tarjeta text-texto transition hover:border-primario/60 active:scale-95">
                    <x-icono nombre="mas" />
                </button>
            </form>
        @endif
    </header>

    {{-- Todo lo de abajo comparte el estado de Alpine: "enviando" se activa
         al enviar cualquier formulario marcado con data-pregunta (el campo de
         abajo o una respuesta rápida). --}}
    <div x-data="{ enviando: false, enviado: '' }"
         @if ($mensajes) x-init="$nextTick(() => window.scrollTo(0, document.body.scrollHeight))" @endif
         @submit="if ($event.target.dataset.pregunta !== undefined) {
             enviado = new FormData($event.target).get('pregunta');
             enviando = true;
             $nextTick(() => window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' }));
         }">

        <x-aviso class="mt-4" />

        @if (! $mensajes)
            {{-- ===================== 2. Conversación vacía ===================== --}}
            <x-tarjeta variante="ia" class="aparecer mt-5 p-4" style="--i: 1">
                <p class="etiqueta flex items-center gap-1.5 text-ia">
                    <x-icono nombre="chispas" class="size-4" /> Coach IA
                </p>
                <p class="mt-2.5 font-display text-lg font-bold leading-tight">
                    Hola, {{ auth()->user()->name }}. ¿En qué te ayudo hoy?
                </p>
                <p class="mt-1.5 text-sm leading-relaxed text-tenue">
                    Pregúntame por la técnica de un ejercicio, cuánto descansar, cuándo subir el peso o con qué
                    reemplazar una máquina.
                </p>
            </x-tarjeta>

            <x-titulo-seccion texto="Prueba con" class="aparecer mt-6" style="--i: 2" />
            <div class="mt-2 flex flex-col items-start gap-2">
                @foreach ($sugerencias as $sugerencia)
                    <form method="POST" action="{{ route('coach.preguntar') }}" data-pregunta
                          class="aparecer" style="--i: {{ 3 + $loop->index }}">
                        @csrf
                        <input type="hidden" name="pregunta" value="{{ $sugerencia }}">
                        <button type="submit" :disabled="enviando"
                                class="rounded-full border border-ia-borde bg-tarjeta px-3.5 py-2 text-left text-sm text-texto transition hover:bg-white/5 active:scale-[0.98] disabled:opacity-50">
                            {{ $sugerencia }}
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
        @else
            {{-- ===================== 3. Mensajes ===================== --}}
            <ol class="mt-4 space-y-3" aria-label="Conversación">
                @foreach ($mensajes as $mensaje)
                    {{-- Solo los dos últimos (la pregunta recién enviada y su
                         respuesta) entran con animación; el resto ya estaba. --}}
                    @php($nuevo = $loop->remaining < 2)

                    @if ($mensaje['rol'] === 'usuario')
                        <li @class(['flex justify-end', 'aparecer' => $nuevo])>
                            <p class="max-w-[85%] whitespace-pre-line rounded-2xl rounded-br-md bg-primario-fuerte px-3.5 py-2.5 text-sm leading-relaxed text-white">{{ $mensaje['texto'] }}</p>
                        </li>
                    @else
                        <li @class(['max-w-[92%]', 'aparecer' => $nuevo]) @if ($nuevo) style="--i: 2" @endif>
                            <div class="rounded-2xl rounded-tl-md border border-borde bg-tarjeta p-3.5">
                                <p class="etiqueta text-ia">Coach IA</p>
                                {{-- La respuesta viene en Markdown. html_input "strip" borra
                                     cualquier HTML que traiga (la IA podría escribirlo):
                                     así {!! !!} solo imprime el formato de Markdown. --}}
                                <div class="respuesta-coach mt-1.5 text-sm leading-relaxed text-texto/90">
                                    {!! Str::markdown($mensaje['texto'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                                </div>

                                @if ($mensaje['ejercicio_id'] && $ejercicios->has($mensaje['ejercicio_id']))
                                    <a href="{{ route('ejercicios.show', $mensaje['ejercicio_id']) }}"
                                       class="mt-3 inline-flex max-w-full items-center gap-1 rounded-lg border border-primario/40 px-2.5 py-1.5 text-xs font-medium text-primario transition hover:bg-primario/10">
                                        <span class="truncate">Ver ficha · {{ $ejercicios[$mensaje['ejercicio_id']] }}</span>
                                        <x-icono nombre="flecha-der" class="size-3.5" />
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endif
                @endforeach

            </ol>

            {{-- ===================== 4. Respuestas rápidas ===================== --}}
            <div x-show="! enviando" class="aparecer mt-3 flex flex-wrap gap-2" style="--i: 4">
                @foreach (array_slice($sugerencias, 0, 2) as $sugerencia)
                    <form method="POST" action="{{ route('coach.preguntar') }}" data-pregunta>
                        @csrf
                        <input type="hidden" name="pregunta" value="{{ $sugerencia }}">
                        <button type="submit"
                                class="rounded-full border border-borde bg-tarjeta px-3 py-1.5 text-xs font-medium text-texto transition hover:border-ia-borde hover:bg-white/5">
                            {{ $sugerencia }}
                        </button>
                    </form>
                @endforeach
            </div>
        @endif

        {{-- Mientras se envía: la pregunta aparece al tiro como burbuja y el
             coach queda "escribiendo…" hasta que llega la respuesta (la página
             se recarga sola). x-cloak lo oculta hasta que Alpine arranca. --}}
        <ol x-cloak x-show="enviando" class="mt-3 space-y-3" aria-live="polite">
            <li class="flex justify-end">
                <p class="max-w-[85%] whitespace-pre-line rounded-2xl rounded-br-md bg-primario-fuerte px-3.5 py-2.5 text-sm leading-relaxed text-white" x-text="enviado"></p>
            </li>
            <li>
                <div class="inline-block rounded-2xl rounded-tl-md border border-borde bg-tarjeta px-3.5 py-3">
                    <p class="etiqueta text-ia">Coach IA</p>
                    <p class="escribiendo mt-2 flex gap-1" role="status" aria-label="El coach está escribiendo">
                        <span class="size-1.5 rounded-full bg-ia"></span>
                        <span class="size-1.5 rounded-full bg-ia"></span>
                        <span class="size-1.5 rounded-full bg-ia"></span>
                    </p>
                </div>
            </li>
        </ol>

        {{-- Espacio para que el último mensaje no quede tapado por el campo fijo. --}}
        <div class="h-24" aria-hidden="true"></div>

        {{-- ===================== 5. Campo para preguntar ===================== --}}
        {{-- Fijo justo encima de la barra de navegación (h-16 = 4rem, más el
             borde seguro del celular). --}}
        <div class="fixed inset-x-0 bottom-[calc(4rem_+_env(safe-area-inset-bottom))] z-10 mx-auto max-w-md border-t border-borde bg-fondo/95 px-4 pt-3 pb-2 backdrop-blur">
            <form method="POST" action="{{ route('coach.preguntar') }}" data-pregunta class="flex items-center gap-2">
                @csrf
                <label for="pregunta" class="sr-only">Pregunta para el coach</label>
                <input id="pregunta" name="pregunta" type="text" required maxlength="{{ $maxPregunta }}"
                       value="{{ $pregunta }}" placeholder="Pregunta sobre un ejercicio…" autocomplete="off"
                       @if ($pregunta !== '') autofocus @endif
                       :readonly="enviando"
                       class="h-11 min-w-0 flex-1 rounded-xl border-borde bg-tarjeta px-3.5 text-sm text-texto placeholder:text-tenue focus:border-ia focus:ring-ia">
                <button type="submit" aria-label="Enviar" :disabled="enviando"
                        class="grid size-11 shrink-0 place-items-center rounded-xl bg-primario-fuerte text-white transition hover:bg-primario active:scale-95 disabled:opacity-60">
                    <x-icono nombre="enviar" class="size-5" />
                </button>
            </form>
            <x-input-error :messages="$errors->get('pregunta')" class="mt-1.5" />
            <p class="mt-1.5 text-center text-[11px] text-tenue">
                Orientación general: no reemplaza a un profesional de la salud.
            </p>
        </div>
    </div>
</x-app-layout>
