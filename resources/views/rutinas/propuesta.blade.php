{{--
    Vista 04 · Rutina generada (GET /rutinas/propuesta).

    La propuesta del coach antes de guardarla: el plan, una pestaña por día
    con sus ejercicios, el "por qué" y los botones Regenerar y Guardar.

      - $propuesta:    ['nombre', 'explicacion', 'dias' => [['nombre', 'ejercicios' => […]]]]
                       (ver la forma completa en App\Support\PropuestaRutina).
      - $preferencias: lo que se eligió en el formulario (nivel, minutos, objetivo…).
      - $ejercicios:   [id => Ejercicio] con los ejercicios de la propuesta.

    Las pestañas son de Alpine: x-data guarda qué día se ve ("dia", empieza
    en 0) y x-show muestra solo la lista de ese día, sin pedir nada al servidor.
--}}
@use('App\Support\Formato')

<x-app-layout titulo="Rutina generada" :navegacion="false">
    <div x-data="{ dia: 0 }">

        {{-- ===================== 1. Volver + etiqueta ===================== --}}
        <div class="aparecer flex items-center gap-3" style="--i: 0">
            {{-- Vuelve al formulario: la propuesta sigue en la sesión hasta guardarla. --}}
            <a href="{{ route('rutinas.generar') }}"
               class="grid size-10 shrink-0 place-items-center rounded-xl border border-borde bg-tarjeta text-texto hover:border-primario/60"
               aria-label="Volver al formulario">
                <x-icono nombre="flecha-izq" />
            </a>
            <span class="inline-flex items-center gap-1.5 rounded-full border border-ia-borde bg-ia-fondo px-3 py-1 text-[11px] font-semibold text-ia">
                <x-icono nombre="chispas" class="size-3.5" /> Propuesta del Coach IA
            </span>
        </div>

        {{-- ===================== 2. Título y resumen ===================== --}}
        <div class="aparecer" style="--i: 1">
            <h1 class="mt-4 font-display text-2xl font-bold leading-tight">
                {{ $propuesta['nombre'] }} · {{ count($propuesta['dias']) }} días
            </h1>
            <p class="mt-1 text-xs text-tenue">
                {{ $preferencias['nivel'] }} · {{ $preferencias['minutos'] }} min · {{ $preferencias['objetivo'] }}
            </p>
        </div>

        {{-- ===================== 3. Pestañas por día ===================== --}}
        <div class="aparecer -mx-4 mt-4 flex gap-2 overflow-x-auto px-4 pb-1" style="--i: 2"
             role="tablist" aria-label="Días de la rutina">
            @foreach ($propuesta['dias'] as $i => $dia)
                <button type="button" role="tab" @click="dia = {{ $i }}"
                        :aria-selected="dia === {{ $i }}"
                        :class="dia === {{ $i }} ? 'border-primario-fuerte bg-primario-fuerte text-white' : 'border-borde bg-tarjeta text-tenue hover:text-texto'"
                        class="shrink-0 whitespace-nowrap rounded-[10px] border px-4 py-2 text-xs font-semibold transition">
                    {{ $dia['nombre'] }}
                </button>
            @endforeach
        </div>

        {{-- ===================== 4. Ejercicios del día elegido ===================== --}}
        {{-- Una lista por día; x-show deja visible solo la del día activo.
             x-cloak esconde las demás mientras Alpine carga (si no, se verían
             todas por un instante). --}}
        <div class="aparecer mt-4" style="--i: 3">
            @foreach ($propuesta['dias'] as $i => $dia)
                <ol class="space-y-2" x-show="dia === {{ $i }}" @if ($i > 0) x-cloak @endif>
                    @foreach ($dia['ejercicios'] as $n => $item)
                        {{-- Si el ejercicio no estuviera en el catálogo se
                             muestra el id; validar() ya lo impide, es solo por si acaso. --}}
                        @php($ejercicio = $ejercicios[$item['ejercicio_id']] ?? null)
                        <li>
                            <x-ejercicio-rutina
                                :numero="$n + 1"
                                :nombre="$ejercicio?->nombre ?? $item['ejercicio_id']"
                                :detalle="collect([
                                    $item['repeticiones'] > 0 ? $item['series'].' × '.$item['repeticiones'] : $item['series'].' series',
                                    $item['descanso_seg'] > 0 ? 'descanso '.Formato::segundos($item['descanso_seg']) : null,
                                ])->filter()->join(' · ')"
                                :lateral="'RPE '.$item['rpe']"
                                :nota="$item['nota']"
                                :href="$ejercicio ? route('ejercicios.show', $ejercicio) : null" />
                        </li>
                    @endforeach
                </ol>
            @endforeach
        </div>

        {{-- ===================== 5. Por qué esta rutina ===================== --}}
        <x-tarjeta variante="ia" class="aparecer mt-4 p-4" style="--i: 4">
            <p class="etiqueta flex items-center gap-1.5 text-ia">
                <x-icono nombre="chispas" class="size-4" /> Por qué esta rutina
            </p>
            <p class="mt-2 text-sm leading-relaxed">{{ $propuesta['explicacion'] }}</p>
        </x-tarjeta>
    </div>

    {{-- ===================== 6. Botones fijos abajo ===================== --}}
    {{-- Cada botón es su propio <form> POST con @csrf. Regenerar vuelve a
         pedir una propuesta con las mismas preferencias; Guardar se conecta
         en el paso 4. --}}
    <x-slot:pie>
        <div class="grid grid-cols-[auto_1fr] gap-2" x-data="{ enviando: false }">
            <form method="POST" action="{{ route('rutinas.regenerar') }}" @submit="enviando = true">
                @csrf
                <button type="submit" x-bind:disabled="enviando"
                        class="flex h-12 items-center justify-center gap-1.5 rounded-2xl border border-borde bg-tarjeta px-4 text-sm font-semibold text-texto hover:bg-white/5 disabled:opacity-60">
                    <x-icono nombre="rayo" class="size-4" />
                    <span x-text="enviando ? 'Generando…' : 'Regenerar'">Regenerar</span>
                </button>
            </form>

            @if (Route::has('rutinas.guardar'))
                <form method="POST" action="{{ route('rutinas.guardar') }}">
                    @csrf
                    <button type="submit"
                            class="flex h-12 w-full items-center justify-center rounded-2xl bg-primario-fuerte text-sm font-bold text-white hover:bg-primario">
                        Guardar en mis rutinas
                    </button>
                </form>
            @endif
        </div>
        <p class="mt-2 text-center text-[11px] text-tenue">
            Propuesta generada por IA. Ante dolor o lesión, consulta a un profesional.
        </p>
    </x-slot:pie>
</x-app-layout>
