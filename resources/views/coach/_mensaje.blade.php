{{--
    Un mensaje del chat. Lo usan la página del coach y la respuesta JSON de
    CoachController@preguntar (así el HTML de un mensaje existe una sola vez).

      - $mensaje:    ['rol' => 'usuario'|'coach', 'texto' => …, 'ejercicio_id' => …]
      - $ejercicios: [id => nombre] para el botón "Ver ficha".
      - $nuevo:      true si acaba de llegar (entra con animación).
--}}
@if ($mensaje['rol'] === 'usuario')
    <li @class(['flex justify-end', 'aparecer' => $nuevo])>
        <p class="max-w-[85%] whitespace-pre-line rounded-2xl rounded-br-md bg-gradient-to-b from-[#3f7dff] to-primario-fuerte px-3.5 py-2.5 text-sm leading-relaxed text-white shadow-[0_8px_20px_-12px_rgb(47_107_255)]">{{ $mensaje['texto'] }}</p>
    </li>
@else
    <li @class(['flex max-w-[92%] gap-2', 'aparecer' => $nuevo]) @if ($nuevo) style="--i: 2" @endif>
        <span class="mt-1 grid size-7 shrink-0 place-items-center rounded-lg border border-ia-borde bg-ia-fondo text-ia" aria-hidden="true">
            <x-icono nombre="chispas" class="size-3.5" />
        </span>
        <div class="min-w-0 rounded-2xl rounded-tl-md border border-borde bg-tarjeta/90 p-3.5">
            <p class="etiqueta text-ia">Coach IA</p>
            {{-- La respuesta viene en Markdown. html_input "strip" borra
                 cualquier HTML que traiga (la IA podría escribirlo): así
                 {!! !!} solo imprime el formato de Markdown. --}}
            <div @class(['respuesta-coach mt-1.5 text-sm leading-relaxed text-texto/90', 'revelar' => $nuevo])>
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
