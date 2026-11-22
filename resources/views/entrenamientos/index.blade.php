{{--
    Historial (GET /entrenamientos): las sesiones de la cuenta, de la más
    reciente a la más antigua, 10 por página. Cada tarjeta se despliega para
    ver las series de cada ejercicio, y tiene Editar y Eliminar (con
    confirmación en la misma tarjeta, sin ventanas del navegador).

    Si se llega con #sesion-ID (desde Inicio) o recién se guardó una sesión
    (session('destacar')), esa tarjeta se abre y se resalta.
--}}
@use('App\Support\Formato')

@php($destacar = session('destacar'))

<x-app-layout titulo="Entrenamientos">
    <x-encabezado titulo="Entrenamientos" subtitulo="Tu historial de sesiones" :atras="route('inicio')" class="aparecer" style="--i: 0">
        <a href="{{ route('entrenamientos.create') }}"
           class="boton-primario grid size-10 place-items-center rounded-xl text-white"
           aria-label="Registrar entrenamiento">
            <x-icono nombre="mas" />
        </a>
    </x-encabezado>

    <x-aviso />

    <div class="mt-5 space-y-3">
        @forelse ($entrenamientos as $entrenamiento)
            @php($porEjercicio = $entrenamiento->series->groupBy('ejercicio_id'))
            <article id="sesion-{{ $entrenamiento->id }}"
                     x-data="{ abierto: @js($destacar === $entrenamiento->id), confirmar: false }"
                     x-init="if (location.hash === '#sesion-{{ $entrenamiento->id }}') { abierto = true; $nextTick(() => $el.scrollIntoView({ block: 'center' })) }"
                     @class([
                         'aparecer tarjeta scroll-mt-24 overflow-hidden rounded-2xl border bg-tarjeta/85',
                         'border-primario/70 shadow-[0_0_0_1px_rgb(76_141_255/0.2),0_16px_40px_-20px_rgb(76_141_255/0.7)]' => $destacar === $entrenamiento->id,
                         'border-borde' => $destacar !== $entrenamiento->id,
                     ])
                     style="--i: {{ min(1 + $loop->index, 8) }}">
                <button type="button" @click="abierto = ! abierto" :aria-expanded="abierto"
                        class="flex w-full items-start gap-3 p-4 text-left">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-primario/25 to-primario/5 text-center">
                        <span>
                            <span class="block text-[9px] font-bold uppercase leading-none text-primario/80">{{ $entrenamiento->inicio->isoFormat('MMM') }}</span>
                            <span class="block font-display text-lg font-bold leading-none text-primario">{{ $entrenamiento->inicio->day }}</span>
                        </span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-semibold">{{ $entrenamiento->titulo }}</span>
                        <span class="mt-0.5 block text-xs text-tenue">
                            {{ Formato::fechaLarga($entrenamiento->inicio) }} · {{ Formato::minutos($entrenamiento->duracion_minutos) }}
                        </span>
                        <span class="mt-2 flex flex-wrap gap-1.5 text-[11px] text-tenue">
                            <span class="rounded-full border border-borde px-2 py-0.5">{{ $porEjercicio->count() }} ejercicios</span>
                            <span class="rounded-full border border-borde px-2 py-0.5">{{ $entrenamiento->series->count() }} series</span>
                            @if ($entrenamiento->volumen > 0)
                                <span class="rounded-full border border-primario/30 bg-primario/10 px-2 py-0.5 font-semibold text-primario">{{ Formato::miles($entrenamiento->volumen) }} kg</span>
                            @endif
                        </span>
                    </span>
                    <x-icono nombre="flecha-der" class="mt-1 size-4 shrink-0 text-tenue transition-transform duration-200" ::class="abierto && 'rotate-90'" />
                </button>

                {{-- Detalle: las series de cada ejercicio. --}}
                <div x-show="abierto" x-cloak
                     x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 -translate-y-1"
                     class="border-t border-borde px-4 py-3">
                    <ul class="space-y-2.5">
                        @foreach ($porEjercicio as $series)
                            @php($ejercicio = $series->first()->ejercicio)
                            <li>
                                <a href="{{ $ejercicio ? route('ejercicios.show', $ejercicio) : '#' }}" class="text-sm font-semibold hover:text-primario">
                                    {{ $ejercicio?->nombre ?? 'Ejercicio' }}
                                </a>
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    @foreach ($series as $serie)
                                        <span class="rounded-lg bg-tarjeta-alta px-2 py-1 text-[11px] tabular-nums text-texto/90">
                                            {{ $serie->kg !== null ? str_replace('.', ',', rtrim(rtrim(number_format($serie->kg, 2, '.', ''), '0'), '.')).' kg × ' : '× ' }}{{ $serie->reps }}
                                            @if ($serie->rpe)<span class="text-tenue">· RPE {{ $serie->rpe }}</span>@endif
                                        </span>
                                    @endforeach
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="flex border-t border-borde" x-show="! confirmar">
                    <a href="{{ route('entrenamientos.edit', $entrenamiento) }}"
                       class="flex flex-1 items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-primario transition hover:bg-white/5">
                        <x-icono nombre="editar" class="size-4" /> Editar
                    </a>
                    <button type="button" @click="confirmar = true"
                            class="flex flex-1 items-center justify-center gap-1.5 border-l border-borde px-4 py-2.5 text-sm font-semibold text-error transition hover:bg-error/5">
                        <x-icono nombre="basura" class="size-4" /> Eliminar
                    </button>
                </div>
                <form method="POST" action="{{ route('entrenamientos.destroy', $entrenamiento) }}" x-show="confirmar" x-cloak
                      x-transition.opacity class="flex items-center gap-2 border-t border-error/30 bg-error/[0.06] px-4 py-2">
                    @csrf @method('DELETE')
                    <span class="flex-1 text-xs text-texto/90">¿Eliminar esta sesión del historial?</span>
                    <button type="button" @click="confirmar = false" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-tenue hover:text-texto">Cancelar</button>
                    <button class="rounded-lg bg-error px-3 py-1.5 text-xs font-bold text-[#2a1418] hover:brightness-110">Eliminar</button>
                </form>
            </article>
        @empty
            <div class="aparecer rounded-2xl border border-dashed border-borde px-4 py-12 text-center" style="--i: 1">
                <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-primario/10 text-primario animate-flotar">
                    <x-icono nombre="pesa" class="size-7" />
                </span>
                <p class="mt-4 font-semibold">Aún no registras entrenamientos</p>
                <p class="mt-1 text-sm text-tenue">Guarda tus series para ver tu progreso y recibir mejores sugerencias.</p>
                <a href="{{ route('entrenamientos.create') }}"
                   class="boton-primario mt-5 inline-flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-semibold text-white">
                    <x-icono nombre="mas" class="size-4" /> Registrar el primero
                </a>
            </div>
        @endforelse
    </div>

    <x-paginacion :paginas="$entrenamientos" class="mt-5" />
</x-app-layout>
