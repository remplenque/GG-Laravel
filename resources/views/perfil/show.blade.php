{{--
    Perfil (GET /perfil): la cuenta, el plan de los próximos días y el
    progreso. $user, $hoy, $proximos, $progreso, $mapa, $volumenSemanal y
    $marcas los manda PerfilController.
--}}
@use('App\Support\Formato')

@php
    $maxVolumen = max(1, collect($volumenSemanal)->max('volumen'));
    $volumenActual = end($volumenSemanal)['volumen'] ?? 0;
    $volumenAnterior = $volumenSemanal[count($volumenSemanal) - 2]['volumen'] ?? 0;
    $variacion = $volumenAnterior > 0 ? (int) round(($volumenActual - $volumenAnterior) / $volumenAnterior * 100) : null;
    $nombresDias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
@endphp

<x-app-layout titulo="Perfil">
    <x-encabezado titulo="Perfil" subtitulo="Tu cuenta y plan de entrenamiento" class="aparecer" style="--i: 0">
        <a href="{{ route('perfil.editar') }}"
           class="presionable flex h-10 items-center gap-1.5 rounded-xl border border-borde bg-tarjeta px-3 text-xs font-semibold transition hover:border-primario/60">
            <x-icono nombre="editar" class="size-4 text-primario" /> Editar
        </a>
    </x-encabezado>

    <x-aviso />

    {{-- ===================== Tarjeta de la cuenta ===================== --}}
    <x-tarjeta class="aparecer relative mt-4 overflow-hidden p-5" style="--i: 1">
        <div class="pointer-events-none absolute -right-16 -top-20 size-56 rounded-full bg-primario/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-24 -left-10 size-48 rounded-full bg-ia/10 blur-3xl" aria-hidden="true"></div>
        <div class="relative flex items-center gap-4">
            <div class="grid size-16 shrink-0 place-items-center rounded-2xl border border-primario/50 bg-gradient-to-br from-primario/30 to-primario/5 font-display text-xl font-bold text-primario shadow-lg shadow-primario/20">
                {{ $user->iniciales ?: '?' }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate font-display text-xl font-bold">{{ trim($user->name.' '.$user->apellido) ?: 'Tu perfil' }}</p>
                <p class="truncate text-sm text-tenue">{{ $user->email }}</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @if ($user->nivel)
                        <span class="inline-flex rounded-full border border-primario/30 bg-primario/10 px-2.5 py-1 text-[11px] font-bold text-primario">Nivel {{ $user->nivel }}</span>
                    @endif
                    <span class="inline-flex items-center gap-1 rounded-full border border-borde bg-tarjeta-alta px-2.5 py-1 text-[11px] font-semibold text-tenue">
                        <x-icono nombre="pesa" class="size-3" /> {{ $progreso['total'] }} {{ $progreso['total'] === 1 ? 'sesión' : 'sesiones' }} en total
                    </span>
                </div>
            </div>
        </div>
    </x-tarjeta>

    {{-- ===================== Próximos entrenamientos ===================== --}}
    <x-titulo-seccion texto="Próximos entrenamientos" accion="Ver rutinas" :href="route('rutinas.index')"
                      class="aparecer mt-7" style="--i: 2" />
    <div class="mt-2 space-y-2">
        @forelse ($proximos as $proximo)
            @php
                $fecha = $proximo['fecha'];
                $rutina = $proximo['rutina'];
                $dia = $fecha->isSameDay($hoy)
                    ? 'Hoy'
                    : ($fecha->isSameDay($hoy->addDay()) ? 'Mañana' : Formato::nombreDia($fecha));
            @endphp

            <x-tarjeta :variante="$loop->first ? 'destacada' : 'normal'" class="aparecer flex items-center gap-3 px-3 py-3" style="--i: {{ 3 + $loop->index }}">
                <div @class([
                    'grid size-12 shrink-0 place-items-center rounded-xl border text-center',
                    'border-primario/60 bg-primario/15 text-primario' => $loop->first,
                    'border-borde bg-tarjeta-alta text-tenue' => ! $loop->first,
                ])>
                    <span class="block text-[10px] font-bold uppercase leading-none">{{ $fecha->isoFormat('MMM') }}</span>
                    <span class="block font-display text-lg font-bold leading-none">{{ $fecha->day }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-primario">{{ $dia }} · {{ $user->hora_entrenamiento ? substr($user->hora_entrenamiento, 0, 5) : 'Horario libre' }}</p>
                    <p class="truncate text-sm font-bold">{{ $rutina?->nombre ?? 'Sesión por definir' }}</p>
                    <p class="truncate text-xs text-tenue">{{ $rutina?->resumen ?? 'Crea una rutina para completar tu calendario' }}</p>
                </div>
                @if ($rutina)
                    <a href="{{ route('entrenamientos.create', ['rutina' => $rutina->id]) }}"
                       class="boton-primario grid size-10 shrink-0 place-items-center rounded-xl text-white"
                       aria-label="Empezar {{ $rutina->nombre }}">
                        <x-icono nombre="play" class="size-3.5" />
                    </a>
                @else
                    <a href="{{ route('rutinas.generar') }}" class="text-primario" aria-label="Crear rutina"><x-icono nombre="flecha-der" /></a>
                @endif
            </x-tarjeta>
        @empty
            <x-tarjeta class="aparecer mt-2 border-dashed py-6 text-center" style="--i: 3">
                <x-icono nombre="calendario" class="mx-auto size-7 text-tenue" />
                <p class="mt-2 text-sm font-bold">Aún no tienes días programados</p>
                <p class="mt-1 text-xs text-tenue">Elige tus días de entrenamiento para ver tu calendario.</p>
                <a href="{{ route('perfil.editar') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-primario hover:underline">
                    Elegir mis días <x-icono nombre="flecha-der" class="size-4" />
                </a>
            </x-tarjeta>
        @endforelse
    </div>

    {{-- ===================== Progreso del mes ===================== --}}
    <x-titulo-seccion texto="Tu progreso este mes" class="aparecer mt-7" style="--i: 6" />
    <div class="mt-2 grid grid-cols-3 gap-2">
        <x-stat-card class="aparecer" style="--i: 7" icono="calendario" :valor="$progreso['sesiones']" :contar="$progreso['sesiones']" :retraso="300" etiqueta="Sesiones" />
        <x-stat-card class="aparecer" style="--i: 8" icono="reloj" :valor="Formato::minutos($progreso['minutos'])" etiqueta="Entrenando" />
        <x-stat-card class="aparecer" style="--i: 9" icono="llama" :valor="$progreso['racha']" :contar="$progreso['racha']" :retraso="380" unidad="días" etiqueta="Racha actual" />
    </div>

    {{-- ===================== Constancia (12 semanas) ===================== --}}
    <x-tarjeta class="aparecer mt-3 p-4" style="--i: 10">
        <div class="flex items-center justify-between">
            <p class="text-sm font-semibold">Constancia</p>
            <p class="text-[11px] text-tenue">Últimas 12 semanas</p>
        </div>
        <div class="mx-auto mt-3 flex max-w-md gap-2">
            <div class="grid grid-rows-7 gap-1 pr-0.5 text-[9px] leading-none text-tenue" aria-hidden="true">
                @foreach (['L', '', 'M', '', 'V', '', 'D'] as $letra)
                    <span class="flex items-center">{{ $letra }}</span>
                @endforeach
            </div>
            <ol class="grid flex-1 grid-cols-12 gap-1" aria-label="Días entrenados en las últimas 12 semanas">
                @foreach ($mapa as $s => $semana)
                    <li class="grid grid-rows-7 gap-1">
                        @foreach ($semana as $d => $celda)
                            <span title="{{ Formato::fechaLarga($celda['fecha']) }}: {{ $celda['sesiones'] }} {{ $celda['sesiones'] === 1 ? 'sesión' : 'sesiones' }}"
                                  style="--retraso: {{ ($s * 7 + $d) * 6 }}ms"
                                  @class([
                                      'crecer aspect-square rounded-[4px]',
                                      'border border-dashed border-borde/60' => $celda['futuro'],
                                      'bg-tarjeta-alta' => ! $celda['futuro'] && $celda['sesiones'] === 0,
                                      'bg-primario/55' => $celda['sesiones'] === 1,
                                      'bg-primario shadow-[0_0_8px_rgb(76_141_255/0.6)]' => $celda['sesiones'] >= 2,
                                      'ring-1 ring-texto/70 ring-offset-1 ring-offset-tarjeta' => $celda['fecha']->isSameDay($hoy),
                                  ])></span>
                        @endforeach
                    </li>
                @endforeach
            </ol>
        </div>
        <div class="mx-auto mt-3 flex max-w-md items-center justify-end gap-1.5 text-[10px] text-tenue" aria-hidden="true">
            Menos
            <span class="size-2.5 rounded-[3px] bg-tarjeta-alta"></span>
            <span class="size-2.5 rounded-[3px] bg-primario/55"></span>
            <span class="size-2.5 rounded-[3px] bg-primario"></span>
            Más
        </div>
    </x-tarjeta>

    {{-- ===================== Volumen por semana ===================== --}}
    <x-tarjeta class="aparecer mt-3 p-4" style="--i: 11">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm font-semibold">Volumen por semana</p>
                <p class="mt-0.5 font-display text-2xl font-bold tabular-nums">
                    {{ Formato::miles($volumenActual) }} <span class="font-sans text-xs font-medium text-tenue">kg esta semana</span>
                </p>
            </div>
            @if ($variacion !== null)
                <span @class([
                    'mt-1 shrink-0 rounded-full px-2 py-1 text-[11px] font-bold tabular-nums',
                    'bg-exito/15 text-exito' => $variacion >= 0,
                    'bg-error/15 text-error' => $variacion < 0,
                ])>{{ $variacion >= 0 ? '+' : '' }}{{ $variacion }}%</span>
            @endif
        </div>
        <ol class="mt-4 flex h-32 items-end gap-2" aria-label="Volumen de las últimas 8 semanas">
            @foreach ($volumenSemanal as $semana)
                @php($alto = $semana['volumen'] > 0 ? max(6, round($semana['volumen'] / $maxVolumen * 100)) : 3)
                <li class="group flex h-full flex-1 flex-col items-center justify-end gap-1.5" title="Semana del {{ $semana['desde']->isoFormat('D MMM') }}: {{ Formato::miles($semana['volumen']) }} kg">
                    <span class="text-[9px] tabular-nums text-tenue opacity-0 transition group-hover:opacity-100">{{ $semana['volumen'] >= 1000 ? round($semana['volumen'] / 1000, 1).'k' : Formato::miles($semana['volumen']) }}</span>
                    <span style="height: {{ $alto }}%; --retraso: {{ 200 + $loop->index * 60 }}ms"
                          @class([
                              'barra-crece w-full rounded-t-md rounded-b-sm transition-colors',
                              'bg-gradient-to-t from-primario-fuerte to-ia' => $loop->last,
                              'bg-primario/35 group-hover:bg-primario/60' => ! $loop->last,
                          ])></span>
                    <span @class(['text-[9px] tabular-nums', 'font-bold text-texto' => $loop->last, 'text-tenue' => ! $loop->last])>{{ $semana['desde']->format('d/m') }}</span>
                </li>
            @endforeach
        </ol>
    </x-tarjeta>

    {{-- ===================== Mejores marcas ===================== --}}
    @if ($marcas->isNotEmpty())
        <x-titulo-seccion texto="Mejores marcas" class="aparecer mt-7" style="--i: 12" />
        <div class="mt-2 grid grid-cols-2 gap-2">
            @foreach ($marcas as $marca)
                <x-tarjeta :href="route('ejercicios.show', $marca->ejercicio_id)" class="aparecer relative overflow-hidden px-3 py-3" style="--i: {{ 13 + $loop->index }}">
                    <x-icono nombre="trofeo" :class="'absolute right-2.5 top-2.5 size-4 '.($loop->first ? 'text-fuego' : 'text-tenue/50')" />
                    <p class="font-display text-xl font-bold tabular-nums">{{ str_replace('.', ',', rtrim(rtrim(number_format((float) $marca->mejor, 2, '.', ''), '0'), '.')) }}<span class="ml-1 font-sans text-xs font-medium text-tenue">kg</span></p>
                    <p class="mt-0.5 truncate pr-4 text-xs text-tenue">{{ $marca->ejercicio?->nombre ?? $marca->ejercicio_id }}</p>
                </x-tarjeta>
            @endforeach
        </div>
    @endif

    {{-- ===================== Datos personales ===================== --}}
    <x-titulo-seccion texto="Datos personales" accion="Editar" :href="route('perfil.editar')" class="aparecer mt-7" style="--i: 17" />
    <x-tarjeta class="aparecer mt-2 divide-y divide-borde !p-0" style="--i: 18">
        <div class="flex items-center gap-3 px-4 py-3">
            <span class="grid size-9 place-items-center rounded-xl bg-primario/10 text-primario"><x-icono nombre="objetivo" /></span>
            <div class="min-w-0 flex-1"><p class="text-xs text-tenue">Objetivo</p><p class="truncate text-sm font-semibold">{{ $user->objetivo ?: 'Sin definir' }}</p></div>
        </div>
        <div class="flex items-center gap-3 px-4 py-3">
            <span class="grid size-9 place-items-center rounded-xl bg-primario/10 text-primario"><x-icono nombre="balanza" /></span>
            <div class="min-w-0 flex-1"><p class="text-xs text-tenue">Peso</p><p class="text-sm font-semibold">{{ $user->peso_kg ? str_replace('.', ',', $user->peso_kg).' kg' : 'Sin registrar' }}</p></div>
        </div>
        <div class="flex items-center gap-3 px-4 py-3">
            <span class="grid size-9 place-items-center rounded-xl bg-primario/10 text-primario"><x-icono nombre="reloj" /></span>
            <div class="min-w-0 flex-1"><p class="text-xs text-tenue">Horario habitual</p><p class="text-sm font-semibold">{{ $user->hora_entrenamiento ? substr($user->hora_entrenamiento, 0, 5).' hrs' : 'Horario libre' }}</p></div>
        </div>
        <div class="px-4 py-3">
            <p class="text-xs text-tenue">Días de entrenamiento</p>
            <div class="mt-2 flex flex-wrap gap-1.5">
                @foreach ($nombresDias as $indice => $nombreDia)
                    <span @class([
                        'rounded-lg border px-2.5 py-1 text-[11px] font-bold',
                        'border-primario/40 bg-primario/15 text-primario' => in_array($indice, $user->dias_entrenamiento ?? [], true),
                        'border-borde text-tenue' => ! in_array($indice, $user->dias_entrenamiento ?? [], true),
                    ])>{{ $nombreDia }}</span>
                @endforeach
            </div>
        </div>
    </x-tarjeta>

    <form method="POST" action="{{ route('logout') }}" class="aparecer mt-4" style="--i: 19">
        @csrf
        <button type="submit" class="presionable flex w-full items-center justify-center gap-2 rounded-xl border border-error/30 bg-error/5 px-4 py-3 text-sm font-semibold text-error transition hover:bg-error/10">
            <x-icono nombre="salir" /> Cerrar sesión
        </button>
    </form>
</x-app-layout>
