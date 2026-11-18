{{--
    Editar rutina (GET /rutinas/{rutina}/editar).

    Cambia el nombre y, por cada ejercicio, las series, repeticiones y
    descanso; también permite quitar ejercicios o borrar la rutina completa.
    Es lo que promete "Puedes editarla después" en el formulario de la IA.

    Los campos de cada ejercicio se llaman ejercicios[ID][campo], donde ID es
    el id de su fila en rutina_ejercicios: así llegan agrupados por ejercicio
    (ver ActualizarRutinaRequest). old('ejercicios.ID.campo', valor) deja lo
    escrito si la validación falla.

    $rutina la manda RutinaController@editar, con sus ejercicios cargados.
--}}
@php
    // Clases de los campos numéricos, iguales a los del resto de los formularios.
    $campo = 'w-full rounded-lg border-borde bg-fondo px-2 py-1.5 text-center text-sm text-texto focus:border-primario focus:ring-primario';
@endphp

<x-app-layout titulo="Editar rutina" :navegacion="false">
    <x-encabezado titulo="Editar rutina" :etiqueta="$rutina->nombre" :atras="route('rutinas.show', $rutina)"
                  class="aparecer" style="--i: 0" />

    {{-- @method('PUT'): los formularios solo mandan GET o POST, así que
         Laravel lee este campo escondido y trata el envío como PUT. --}}
    <form id="form-editar" method="POST" action="{{ route('rutinas.actualizar', $rutina) }}" class="mt-5 space-y-4">
        @csrf
        @method('PUT')

        {{-- ===================== Nombre ===================== --}}
        <div class="aparecer" style="--i: 1">
            <label for="nombre" class="text-sm font-semibold">Nombre</label>
            <input id="nombre" name="nombre" type="text" maxlength="40" required
                   value="{{ old('nombre', $rutina->nombre) }}"
                   class="mt-2 w-full rounded-xl border-borde bg-tarjeta text-sm text-texto focus:border-primario focus:ring-primario">
            <x-input-error :messages="$errors->get('nombre')" class="mt-1.5" />
        </div>

        {{-- ===================== Ejercicios ===================== --}}
        <section class="aparecer" style="--i: 2">
            <x-titulo-seccion texto="Ejercicios" />

            <ol class="mt-2 space-y-2">
                @forelse ($rutina->ejercicios as $i => $item)
                    {{-- x-data por fila: "quitar" atenúa la tarjeta cuando se
                         marca la casilla, para que se note antes de guardar. --}}
                    <li x-data="{ quitar: @js((bool) old("ejercicios.{$item->id}.quitar")) }"
                        :class="quitar && 'opacity-50'"
                        class="rounded-2xl border border-borde bg-tarjeta p-3 transition">
                        <div class="flex items-center gap-3">
                            <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-primario-fuerte text-xs font-bold text-white">{{ $i + 1 }}</span>
                            <p class="min-w-0 flex-1 truncate text-sm font-semibold">{{ $item->ejercicio->nombre }}</p>
                            <label class="flex shrink-0 items-center gap-1.5 text-xs text-tenue">
                                <input type="checkbox" name="ejercicios[{{ $item->id }}][quitar]" value="1" x-model="quitar"
                                       class="rounded border-borde bg-fondo text-error focus:ring-error">
                                Quitar
                            </label>
                        </div>

                        {{-- Tres números lado a lado. --}}
                        <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                            <label class="text-[11px] text-tenue">
                                Series
                                <input type="number" min="1" max="10" name="ejercicios[{{ $item->id }}][series]"
                                       value="{{ old("ejercicios.{$item->id}.series", $item->series) }}" class="{{ $campo }} mt-1">
                            </label>
                            <label class="text-[11px] text-tenue">
                                Reps <span class="text-tenue/70">(0 = tiempo)</span>
                                <input type="number" min="0" max="50" name="ejercicios[{{ $item->id }}][repeticiones]"
                                       value="{{ old("ejercicios.{$item->id}.repeticiones", $item->repeticiones) }}" class="{{ $campo }} mt-1">
                            </label>
                            <label class="text-[11px] text-tenue">
                                Descanso (s)
                                <input type="number" min="0" max="600" step="15" name="ejercicios[{{ $item->id }}][descanso_seg]"
                                       value="{{ old("ejercicios.{$item->id}.descanso_seg", $item->descanso_seg) }}" class="{{ $campo }} mt-1">
                            </label>
                        </div>

                        {{-- Errores de esta fila: get('ejercicios.ID.*') junta los
                             de sus tres campos; flatten() los deja en una lista. --}}
                        <x-input-error :messages="collect($errors->get('ejercicios.'.$item->id.'.*'))->flatten()->all()" class="mt-1.5" />
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-tenue">Esta rutina no tiene ejercicios. Agrégalos desde la biblioteca.</li>
                @endforelse
            </ol>
        </section>
    </form>

    {{-- ===================== Borrar rutina ===================== --}}
    {{-- Formulario aparte con @method('DELETE'). Para no borrar por un toque
         accidental, el primer clic solo muestra la confirmación (Alpine). --}}
    <form method="POST" action="{{ route('rutinas.eliminar', $rutina) }}" x-data="{ confirmar: false }"
          class="aparecer mt-6 text-center" style="--i: 3">
        @csrf
        @method('DELETE')

        <button type="button" x-show="! confirmar" @click="confirmar = true"
                class="text-sm font-semibold text-error hover:underline">
            Borrar rutina
        </button>
        <div x-show="confirmar" x-cloak class="flex items-center justify-center gap-3 text-sm">
            <span class="text-tenue">¿Seguro? Tu historial se conserva.</span>
            <button type="submit" class="font-semibold text-error hover:underline">Sí, borrar</button>
            <button type="button" @click="confirmar = false" class="text-tenue hover:text-texto">Cancelar</button>
        </div>
    </form>

    {{-- ===================== Botón fijo abajo ===================== --}}
    <x-slot:pie>
        <button type="submit" form="form-editar"
                class="flex h-12 w-full items-center justify-center rounded-2xl bg-primario-fuerte text-sm font-bold text-white hover:bg-primario">
            Guardar cambios
        </button>
    </x-slot:pie>
</x-app-layout>
