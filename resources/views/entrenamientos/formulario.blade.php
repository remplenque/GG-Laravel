@php
    $editando = $entrenamiento !== null;
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
@endphp

<x-app-layout :titulo="$editando ? 'Editar entrenamiento' : 'Registrar entrenamiento'" :navegacion="! $editando">
    <x-encabezado
        :titulo="$editando ? 'Editar sesión' : ($rutina?->nombre ?? 'Entrenamiento libre')"
        :etiqueta="$editando ? 'Historial' : 'Registrar entrenamiento'"
        :subtitulo="$rutina?->resumen"
        :atras="$editando ? route('entrenamientos.index') : ($rutina ? route('rutinas.show', $rutina) : route('inicio'))" />

    <x-aviso class="mt-4" />

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-error/50 bg-error/10 px-3 py-2 text-sm text-error" role="alert">
            <p class="font-semibold">Revisa los datos del entrenamiento.</p>
            <ul class="mt-1 list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @unless ($editando)
        <x-tarjeta class="mt-4 space-y-3">
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
                    </form>
                </div>
            @endif
        </x-tarjeta>
    @endunless

    @if ($ejercicios->isEmpty())
        <x-tarjeta class="mt-4 border-dashed py-8 text-center">
            <span class="mx-auto grid size-11 place-items-center rounded-xl bg-primario/15 text-primario">
                <x-icono nombre="pesa" />
            </span>
            <p class="mt-3 font-semibold">Elige una rutina o un ejercicio</p>
            <p class="mt-1 text-sm text-tenue">Luego podrás registrar peso, repeticiones y RPE por serie.</p>
        </x-tarjeta>
    @else
        <form method="POST"
              action="{{ $editando ? route('entrenamientos.update', $entrenamiento) : route('entrenamientos.store') }}"
              class="mt-4" x-data="{ paso: 0, total: {{ $ejercicios->count() }} }">
            @csrf
            @if ($editando) @method('PUT') @endif
            <input type="hidden" name="rutina_id" value="{{ old('rutina_id', $rutina?->id) }}">

            <x-tarjeta class="mb-3 grid grid-cols-2 gap-3">
                <label class="col-span-2 text-xs font-semibold text-tenue">
                    Nombre de la sesión
                    <input name="nombre" value="{{ $nombre }}" maxlength="80"
                           class="mt-1 w-full rounded-xl border-borde bg-tarjeta-alta text-sm focus:border-primario focus:ring-primario">
                </label>
                <label class="text-xs font-semibold text-tenue">
                    Inicio
                    <input type="datetime-local" name="inicio" value="{{ $inicio }}"
                           class="mt-1 w-full rounded-xl border-borde bg-tarjeta-alta text-sm focus:border-primario focus:ring-primario">
                </label>
                <label class="text-xs font-semibold text-tenue">
                    Duración (min)
                    <input type="number" name="duracion" value="{{ $duracion }}" min="1" max="600"
                           class="mt-1 w-full rounded-xl border-borde bg-tarjeta-alta text-sm focus:border-primario focus:ring-primario">
                </label>
            </x-tarjeta>

            <div class="mb-4 h-1.5 overflow-hidden rounded-full bg-tarjeta-alta" aria-hidden="true">
                <div class="h-full rounded-full bg-primario transition-all" :style="`width: ${((paso + 1) / total) * 100}%`"></div>
            </div>

            @foreach ($ejercicios as $indice => $ejercicio)
                <section x-show="paso === {{ $indice }}" x-cloak>
                    <div class="mb-3 flex items-end gap-3">
                        <span class="grid size-9 place-items-center rounded-xl bg-primario/20 text-sm font-bold text-primario">{{ $indice + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-tenue">Ejercicio {{ $indice + 1 }} de {{ $ejercicios->count() }}</p>
                            <h2 class="truncate font-display text-xl font-bold">{{ $ejercicio['nombre'] }}</h2>
                            <p class="text-xs text-tenue">{{ $ejercicio['grupo'] }}</p>
                        </div>
                    </div>

                    <x-tarjeta x-data="{ filas: {{ max(1, min(8, count($ejercicio['series']))) }} }">
                        <input type="hidden" name="ejercicios[{{ $indice }}][ejercicio_id]" value="{{ $ejercicio['id'] }}">
                        <div class="grid grid-cols-[2rem_1fr_1fr_1fr] gap-2 px-1 text-[10px] font-bold uppercase tracking-wider text-tenue">
                            <span>Serie</span><span>Kg</span><span>Reps</span><span>RPE</span>
                        </div>

                        <div class="mt-2 space-y-2">
                            @for ($serie = 0; $serie < 8; $serie++)
                                <div x-show="filas > {{ $serie }}" class="grid grid-cols-[2rem_1fr_1fr_1fr] items-center gap-2">
                                    <span class="text-center text-sm font-bold text-primario">{{ $serie + 1 }}</span>
                                    <input type="number" step="0.25" min="0" max="9999.99"
                                           name="ejercicios[{{ $indice }}][series][{{ $serie }}][kg]"
                                           value="{{ data_get($ejercicio, "series.{$serie}.kg") }}" placeholder="—"
                                           class="min-w-0 rounded-lg border-borde bg-fondo px-2 py-2 text-sm focus:border-primario focus:ring-primario">
                                    <input type="number" min="1" max="1000"
                                           name="ejercicios[{{ $indice }}][series][{{ $serie }}][reps]"
                                           value="{{ data_get($ejercicio, "series.{$serie}.reps") }}" placeholder="—"
                                           class="min-w-0 rounded-lg border-borde bg-fondo px-2 py-2 text-sm focus:border-primario focus:ring-primario">
                                    <input type="number" min="1" max="10"
                                           name="ejercicios[{{ $indice }}][series][{{ $serie }}][rpe]"
                                           value="{{ data_get($ejercicio, "series.{$serie}.rpe") }}" placeholder="—"
                                           class="min-w-0 rounded-lg border-borde bg-fondo px-2 py-2 text-sm focus:border-primario focus:ring-primario">
                                </div>
                            @endfor
                        </div>

                        <button type="button" x-show="filas < 8" @click="filas++"
                                class="mt-3 flex w-full items-center justify-center gap-1.5 rounded-xl border border-dashed border-borde py-2 text-sm font-semibold text-tenue hover:border-primario hover:text-primario">
                            <x-icono nombre="mas" class="size-4" /> Añadir serie
                        </button>
                    </x-tarjeta>

                    <div class="mt-4 flex gap-2">
                        <button type="button" x-show="paso > 0" @click="paso--"
                                class="rounded-xl border border-borde px-4 py-3 text-sm font-semibold hover:border-primario">
                            Anterior
                        </button>
                        <button type="button" x-show="paso < total - 1" @click="paso++"
                                class="flex-1 rounded-xl bg-primario-fuerte px-4 py-3 text-sm font-semibold text-white hover:bg-primario">
                            Siguiente ejercicio
                        </button>
                        <button type="submit" x-show="paso === total - 1"
                                class="flex-1 rounded-xl bg-primario-fuerte px-4 py-3 text-sm font-semibold text-white hover:bg-primario">
                            {{ $editando ? 'Guardar cambios' : 'Finalizar entrenamiento' }}
                        </button>
                    </div>
                </section>
            @endforeach
        </form>
    @endif
</x-app-layout>
