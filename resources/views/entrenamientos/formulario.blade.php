{{--
    Vista 05 · Registrar entrenamiento (y editar una sesión del historial).

    Arriba se elige la rutina o un ejercicio libre. Después, un ejercicio por
    pantalla con sus series (kg, reps, RPE) y el botón "hecha" de cada serie.
    Lo que pasa en el navegador (pasos, cronómetro, descanso, copiar la serie
    anterior) vive en resources/js/modulos/registro.js; sin JavaScript el
    formulario se envía igual con todas las series.

    Variables: $rutinas, $rutina, $catalogo, $ejerciciosFormulario,
    $marcasPrevias (la última vez de cada ejercicio) y $entrenamiento (null al
    registrar). Las manda EntrenamientoController.
--}}
@php
    $editando = $entrenamiento !== null;
    $marcasPrevias ??= [];
    $porId = collect($ejerciciosFormulario)->keyBy('id');
    $ejercicios = collect(old('ejercicios', $ejerciciosFormulario))->map(function ($ejercicio) use ($porId) {
        $id = $ejercicio['ejercicio_id'] ?? $ejercicio['id'];
        $base = $porId->get($id, []);

        return [
            'id' => $id,
            'nombre' => $base['nombre'] ?? 'Ejercicio',
            'grupo' => $base['grupo'] ?? null,
            'series' => $ejercicio['series'] ?? $base['series'] ?? [],
        ];
    })->values();
    $inicio = old('inicio', $entrenamiento?->inicio?->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'));
    $duracion = old('duracion', $entrenamiento?->duracion_minutos ?: ($rutina?->minutos ?: 60));
    $nombre = old('nombre', $entrenamiento?->nombre ?? $rutina?->nombre ?? ($ejercicios->first()['nombre'] ?? 'Entrenamiento libre'));

    // Descanso planificado de cada ejercicio (segundos), para el temporizador.
    $descansoPorId = $rutina?->ejercicios->pluck('descanso_seg', 'ejercicio_id') ?? collect();
    $descansos = $ejercicios->map(fn ($e) => (int) ($descansoPorId[$e['id']] ?? 90) ?: 90)->values();

    $numero = fn ($valor) => $valor === null ? '' : rtrim(rtrim(number_format((float) $valor, 2, '.', ''), '0'), '.');
    $celda = 'min-w-0 w-full rounded-lg border-borde bg-fondo/70 px-2 py-2 text-center text-sm tabular-nums placeholder:text-tenue/50 focus:border-primario focus:ring-primario';
    $campo = 'mt-1 w-full rounded-xl border-borde bg-tarjeta-alta text-sm focus:border-primario focus:ring-primario';
@endphp

<x-app-layout :titulo="$editando ? 'Editar entrenamiento' : 'Registrar entrenamiento'" :navegacion="$ejercicios->isEmpty() && ! $editando">
    <x-encabezado
        :titulo="$editando ? 'Editar sesión' : ($rutina?->nombre ?? 'Entrenamiento libre')"
        :etiqueta="$editando ? 'Historial' : 'Registrar entrenamiento'"
        :subtitulo="$rutina?->resumen"
        :atras="$editando ? route('entrenamientos.index') : ($rutina ? route('rutinas.show', $rutina) : route('inicio'))"
        class="aparecer" style="--i: 0" />

    <x-aviso />

    @if ($errors->any())
        <div class="aparecer mt-4 rounded-xl border border-error/50 bg-error/10 px-3 py-2 text-sm text-error" role="alert">
            <p class="font-semibold">Revisa los datos del entrenamiento.</p>
            <ul class="mt-1 list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===================== Elegir qué entrenar ===================== --}}
    @unless ($editando)
        <x-tarjeta class="aparecer mt-4 space-y-3" style="--i: 1">
            <div>
                <label for="elegir-rutina" class="text-xs font-semibold text-tenue">Rutina</label>
                <form method="GET" action="{{ route('entrenamientos.create') }}" class="mt-1">
                    <select id="elegir-rutina" name="rutina" onchange="this.form.submit()"
                            class="w-full rounded-xl border-borde bg-tarjeta-alta text-sm focus:border-primario focus:ring-primario">
                        <option value="">Entrenamiento libre</option>
                        @foreach ($rutinas as $opcion)
                            <option value="{{ $opcion->id }}" @selected($rutina?->id === $opcion->id)>
                                {{ $opcion->nombre }} · {{ $opcion->ejercicios->count() }} ejercicios
                            </option>
                        @endforeach
                    </select>
                    <noscript><button class="mt-2 text-sm font-semibold text-primario">Elegir</button></noscript>
                </form>
            </div>

            @if (! $rutina)
                <div>
                    <label for="elegir-ejercicio" class="text-xs font-semibold text-tenue">Ejercicio libre</label>
                    <form method="GET" action="{{ route('entrenamientos.create') }}" class="mt-1">
                        <select id="elegir-ejercicio" name="ejercicio" onchange="this.form.submit()"
                                class="w-full rounded-xl border-borde bg-tarjeta-alta text-sm focus:border-primario focus:ring-primario">
                            <option value="">Elige un ejercicio…</option>
                            @foreach ($catalogo as $opcion)
                                <option value="{{ $opcion->id }}" @selected(($ejercicios->first()['id'] ?? null) === $opcion->id)>
                                    {{ $opcion->nombre }} · {{ $opcion->grupo }}
                                </option>
                            @endforeach
                        </select>
                        <noscript><button class="mt-2 text-sm font-semibold text-primario">Elegir</button></noscript>
                    </form>
                </div>
            @endif
        </x-tarjeta>
    @endunless

    @if ($ejercicios->isEmpty())
        <div class="aparecer mt-4 rounded-2xl border border-dashed border-borde px-4 py-10 text-center" style="--i: 2">
            <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-primario/10 text-primario animate-flotar">
                <x-icono nombre="pesa" class="size-7" />
            </span>
            <p class="mt-4 font-semibold">Elige una rutina o un ejercicio</p>
            <p class="mt-1 text-sm text-tenue">Luego podrás registrar peso, repeticiones y RPE por serie, con temporizador de descanso.</p>
        </div>
    @else
        <form method="POST"
              action="{{ $editando ? route('entrenamientos.update', $entrenamiento) : route('entrenamientos.store') }}"
              class="mt-4"
              x-data="registro({ total: {{ $ejercicios->count() }}, vivo: @js(! $editando), descansos: @js($descansos) })"
              @submit="alEnviar($event)">
            @csrf
            @if ($editando) @method('PUT') @endif
            <input type="hidden" name="rutina_id" value="{{ old('rutina_id', $rutina?->id) }}">

            {{-- ===================== Cronómetro y avance ===================== --}}
            <div class="aparecer flex items-center gap-3" style="--i: 2">
                <ol class="flex flex-1 gap-1" aria-label="Ejercicios">
                    @foreach ($ejercicios as $indice => $ejercicio)
                        <li class="flex-1">
                            <button type="button" @click="irA({{ $indice }})"
                                    :class="paso >= {{ $indice }} ? 'bg-primario shadow-[0_0_8px_rgb(76_141_255/0.5)]' : 'bg-tarjeta-alta hover:bg-borde'"
                                    class="block h-1.5 w-full rounded-full transition-colors duration-300"
                                    aria-label="Ir a {{ $ejercicio['nombre'] }}"></button>
                        </li>
                    @endforeach
                </ol>
                @unless ($editando)
                    <span x-cloak x-show="vivo"
                          class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-primario/40 bg-primario/10 px-2.5 py-1 font-display text-xs font-bold tabular-nums text-primario"
                          title="Tiempo de la sesión">
                        <x-icono nombre="cronometro" class="size-3.5" />
                        <span x-text="cronometro">00:00</span>
                    </span>
                @endunless
            </div>

            {{-- ===================== Detalles de la sesión ===================== --}}
            <details class="aparecer group mt-3 rounded-2xl border border-borde bg-tarjeta/85" style="--i: 3" @if ($editando || $errors->hasAny(['nombre', 'inicio', 'duracion'])) open @endif>
                <summary class="flex cursor-pointer list-none items-center gap-2 px-3.5 py-3 text-sm">
                    <x-icono nombre="calendario" class="size-4 text-tenue" />
                    <span class="min-w-0 flex-1 truncate"><span class="font-semibold">{{ $nombre }}</span> <span class="text-tenue">· detalles</span></span>
                    <x-icono nombre="flecha-der" class="size-4 text-tenue transition group-open:rotate-90" />
                </summary>
                <div class="grid grid-cols-2 gap-3 border-t border-borde px-3.5 pb-3.5 pt-3">
                    <label class="col-span-2 text-xs font-semibold text-tenue">
                        Nombre de la sesión
                        <input name="nombre" value="{{ $nombre }}" maxlength="80" class="{{ $campo }}">
                    </label>
                    <label class="text-xs font-semibold text-tenue">
                        Inicio
                        <input type="datetime-local" name="inicio" value="{{ $inicio }}" class="{{ $campo }}">
                    </label>
                    <label class="text-xs font-semibold text-tenue">
                        Duración (min)
                        <input type="number" name="duracion" value="{{ $duracion }}" min="1" max="600" inputmode="numeric"
                               @input="duracionTocada = true" class="{{ $campo }}">
                    </label>
                    @unless ($editando)
                        <p class="col-span-2 text-[11px] leading-relaxed text-tenue" x-show="vivo">
                            Si registras en vivo, al terminar usamos el tiempo del cronómetro (a menos que cambies la duración).
                        </p>
                    @endunless
                </div>
            </details>

            {{-- ===================== Un ejercicio por pantalla ===================== --}}
            @foreach ($ejercicios as $indice => $ejercicio)
                @php
                    $previa = $marcasPrevias[$ejercicio['id']] ?? null;
                    $filasIniciales = max(1, min(8, count($ejercicio['series'])));
                @endphp
                <section x-show="paso === {{ $indice }}" @if ($indice > 0) x-cloak @endif
                         x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
                         @if ($errors->has("ejercicios.{$indice}.*")) data-paso-error="{{ $indice }}" @endif
                         class="mt-5">
                    <div class="mb-3 flex items-center gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-primario/30 to-primario/10 font-display text-sm font-bold text-primario">{{ $indice + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-tenue">Ejercicio {{ $indice + 1 }} de {{ $ejercicios->count() }}{{ $ejercicio['grupo'] ? ' · '.$ejercicio['grupo'] : '' }}</p>
                            <h2 class="truncate font-display text-xl font-bold">{{ $ejercicio['nombre'] }}</h2>
                        </div>
                        <a href="{{ route('ejercicios.show', $ejercicio['id']) }}" target="_blank" rel="noopener"
                           class="grid size-9 shrink-0 place-items-center rounded-xl border border-borde bg-tarjeta text-tenue transition hover:border-primario/60 hover:text-primario"
                           aria-label="Ver la ficha de {{ $ejercicio['nombre'] }}" title="Ver ficha">
                            <x-icono nombre="libro" class="size-4" />
                        </a>
                    </div>

                    @if ($previa)
                        <p class="mb-2 flex items-center gap-1.5 rounded-xl border border-ia-borde/60 bg-ia-fondo/60 px-3 py-2 text-xs text-texto/90">
                            <x-icono nombre="chispas" class="size-3.5 shrink-0 text-ia" />
                            <span>
                                Última vez ({{ Str::lower($previa['fecha']) }}):
                                <span class="font-semibold">
                                    {{-- Las series iguales seguidas se juntan: "70 × 8 (×4)". --}}
                                    {{ collect($previa['series'])
                                        ->map(fn ($s) => ($s['kg'] !== null ? str_replace('.', ',', $numero($s['kg'])).' × ' : '× ').($s['reps'] ?? '—'))
                                        ->chunkWhile(fn ($valor, $i, $grupo) => $valor === $grupo->last())
                                        ->map(fn ($grupo) => $grupo->first().($grupo->count() > 1 ? ' (×'.$grupo->count().')' : ''))
                                        ->take(4)
                                        ->join(' · ') }}
                                </span>
                            </span>
                        </p>
                    @endif

                    <x-tarjeta class="!p-3" x-data="{ filas: {{ $filasIniciales }} }">
                        <input type="hidden" name="ejercicios[{{ $indice }}][ejercicio_id]" value="{{ $ejercicio['id'] }}">
                        <div class="grid grid-cols-[1.75rem_1fr_1fr_1fr_2.5rem] items-center gap-2 px-0.5 text-[10px] font-bold uppercase tracking-wider text-tenue">
                            <span class="text-center">Serie</span><span class="text-center">Kg</span><span class="text-center">Reps</span><span class="text-center">RPE</span><span class="text-center">Hecha</span>
                        </div>

                        <div class="mt-2 space-y-1.5">
                            @for ($serie = 0; $serie < 8; $serie++)
                                @php
                                    $sugerida = $previa['series'][$serie] ?? ($previa ? end($previa['series']) : null);
                                @endphp
                                <div data-fila x-show="filas > {{ $serie }}" @if ($serie >= $filasIniciales) x-cloak @endif
                                     x-transition:enter="transition duration-200" x-transition:enter-start="opacity-0 -translate-y-1"
                                     :class="estaHecha({{ $indice }}, {{ $serie }}) ? 'bg-exito/[0.07] ring-1 ring-exito/30' : ''"
                                     class="grid grid-cols-[1.75rem_1fr_1fr_1fr_2.5rem] items-center gap-2 rounded-xl p-0.5 transition-colors">
                                    <span class="text-center font-display text-sm font-bold text-primario">{{ $serie + 1 }}</span>
                                    <input type="number" step="0.25" min="0" max="9999.99" inputmode="decimal" data-campo="kg"
                                           name="ejercicios[{{ $indice }}][series][{{ $serie }}][kg]"
                                           value="{{ data_get($ejercicio, "series.{$serie}.kg") }}"
                                           placeholder="{{ $sugerida && $sugerida['kg'] !== null ? $numero($sugerida['kg']) : '—' }}"
                                           aria-label="Kilos de la serie {{ $serie + 1 }}"
                                           class="{{ $celda }}">
                                    <input type="number" min="1" max="1000" inputmode="numeric" data-campo="reps"
                                           name="ejercicios[{{ $indice }}][series][{{ $serie }}][reps]"
                                           value="{{ data_get($ejercicio, "series.{$serie}.reps") }}"
                                           placeholder="{{ $sugerida['reps'] ?? '—' }}"
                                           aria-label="Repeticiones de la serie {{ $serie + 1 }}"
                                           class="{{ $celda }}">
                                    <input type="number" min="1" max="10" inputmode="numeric"
                                           name="ejercicios[{{ $indice }}][series][{{ $serie }}][rpe]"
                                           value="{{ data_get($ejercicio, "series.{$serie}.rpe") }}" placeholder="—"
                                           aria-label="RPE de la serie {{ $serie + 1 }}"
                                           class="{{ $celda }}">
                                    <button type="button" @click="marcar({{ $indice }}, {{ $serie }}, $el)"
                                            :aria-pressed="estaHecha({{ $indice }}, {{ $serie }})"
                                            :class="estaHecha({{ $indice }}, {{ $serie }}) ? 'border-exito bg-exito text-fondo scale-100' : 'border-borde bg-fondo/60 text-transparent hover:border-exito/60'"
                                            class="presionable mx-auto grid size-9 place-items-center rounded-lg border-[1.5px] transition-all duration-200"
                                            aria-label="Marcar la serie {{ $serie + 1 }} como hecha">
                                        <x-icono nombre="check" class="size-4" />
                                    </button>
                                </div>
                            @endfor
                        </div>

                        <button type="button" x-show="filas < 8" @click="filas++"
                                class="presionable mt-3 flex w-full items-center justify-center gap-1.5 rounded-xl border border-dashed border-borde py-2.5 text-sm font-semibold text-tenue transition hover:border-primario hover:text-primario">
                            <x-icono nombre="mas" class="size-4" /> Añadir serie
                        </button>
                    </x-tarjeta>

                    <p class="mt-2 text-center text-[11px] text-tenue">
                        RPE: esfuerzo de 1 (muy fácil) a 10 (al fallo). Las series sin repeticiones no se guardan.
                    </p>
                </section>
            @endforeach

            {{-- Espacio para que la barra fija no tape lo último. --}}
            <div class="h-24" aria-hidden="true"></div>

            {{-- ===================== Descanso ===================== --}}
            <div x-cloak x-show="descanso.activo"
                 x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-6" x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="opacity-0 translate-y-6"
                 class="fixed inset-x-0 bottom-[calc(5.5rem_+_env(safe-area-inset-bottom))] z-30 px-4 lg:left-64" role="timer" aria-live="polite">
                <div class="mx-auto flex max-w-md items-center gap-3 rounded-2xl border border-primario/40 bg-[#0f1a33]/95 p-3 shadow-2xl shadow-black/50 backdrop-blur-xl lg:max-w-2xl">
                    <div class="relative grid size-14 shrink-0 place-items-center">
                        <svg class="absolute inset-0 -rotate-90" viewBox="0 0 56 56" aria-hidden="true">
                            <circle cx="28" cy="28" r="24" fill="none" stroke="currentColor" stroke-width="4" class="text-tarjeta-alta" />
                            <circle cx="28" cy="28" r="24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"
                                    class="text-primario transition-[stroke-dashoffset] duration-300"
                                    stroke-dasharray="150.8" :stroke-dashoffset="150.8 * (1 - descansoAvance)" />
                        </svg>
                        <span class="font-display text-sm font-bold tabular-nums" x-text="descansoTexto"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold">Descanso</p>
                        <p class="truncate text-xs text-tenue">Te avisamos al terminar</p>
                    </div>
                    <button type="button" @click="sumarDescanso(15)" class="presionable rounded-lg border border-borde px-2.5 py-2 text-xs font-semibold hover:border-primario/60">+15 s</button>
                    <button type="button" @click="terminarDescanso()" class="presionable rounded-lg bg-primario/15 px-2.5 py-2 text-xs font-semibold text-primario hover:bg-primario/25">Saltar</button>
                </div>
            </div>

            {{-- ===================== Botones fijos ===================== --}}
            <div class="fixed inset-x-0 bottom-0 z-20 border-t border-borde/80 bg-fondo/85 backdrop-blur-xl pb-seguro lg:left-64">
                <div class="mx-auto flex max-w-md gap-2 px-4 py-3 lg:max-w-2xl lg:px-8">
                    <button type="button" x-show="paso > 0" x-cloak @click="irA(paso - 1)"
                            class="presionable flex h-12 items-center gap-1 rounded-2xl border border-borde bg-tarjeta px-4 text-sm font-semibold transition hover:border-primario/60">
                        <x-icono nombre="flecha-izq" class="size-4" /> Anterior
                    </button>
                    <button type="button" x-show="paso < total - 1" @click="irA(paso + 1)"
                            class="boton-primario flex h-12 flex-1 items-center justify-center gap-1 rounded-2xl text-sm font-bold text-white">
                        Siguiente ejercicio <x-icono nombre="flecha-der" class="size-4" />
                    </button>
                    <button type="submit" x-show="paso === total - 1" @if ($ejercicios->count() > 1) x-cloak @endif
                            class="boton-primario flex h-12 flex-1 items-center justify-center gap-2 rounded-2xl text-sm font-bold text-white">
                        <x-icono nombre="check" class="size-4" />
                        {{ $editando ? 'Guardar cambios' : 'Finalizar entrenamiento' }}
                    </button>
                </div>
            </div>
        </form>
    @endif
</x-app-layout>
