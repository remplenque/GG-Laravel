{{--
    Vista 01 · Inicio (dashboard), según el mockup de la Unidad 3.

    De arriba hacia abajo: saludo con el menú de cuenta, la racha de la semana
    con la meta semanal, tres números de la semana, la tarjeta violeta del
    Coach IA con la sugerencia de hoy y las últimas sesiones.

    Las variables ($user, $hoy, $racha, $meta, $perfilIncompleto, $estaSemana,
    $siguiente, $sugerencia y $ultimas) las manda InicioController: la vista
    solo las pinta, no calcula.

    Animaciones (resources/css/app.css y resources/js): cada bloque entra con
    "aparecer" en cascada (--i), los cuadritos de la racha con "crecer", los
    números cuentan desde 0 con x-contar y la llama "late" si hay racha. Con
    "reducir movimiento" activado en el sistema, todo aparece directo.
--}}
@use('App\Support\Formato')

@php
    $hechas = $estaSemana['sesiones'];
    $avanceMeta = min(100, (int) round($hechas / max(1, $meta) * 100));
    $saludo = match (true) {
        $hoy->hour < 5 => 'Entrenando de madrugada',
        $hoy->hour < 12 => 'Buenos días',
        $hoy->hour < 20 => 'Buenas tardes',
        default => 'Buenas noches',
    };
@endphp

<x-app-layout titulo="Inicio">

    {{-- ===================== 1. Saludo y menú de cuenta ===================== --}}
    <header class="aparecer relative z-20 flex items-center gap-3" style="--i: 0">
        <div class="min-w-0 flex-1">
            <p class="text-xs text-tenue">{{ Formato::fechaLarga($hoy) }} · {{ $saludo }}</p>
            <h1 class="truncate font-display text-2xl font-bold lg:text-3xl">
                {{ $user->name ? 'Hola, '.$user->name : 'Hola' }}
            </h1>
        </div>

        {{-- Avatar con las iniciales que abre un menú (Alpine.js). --}}
        <div class="relative" x-data="{ abierto: false }" @keydown.escape.window="abierto = false">
            <button type="button" @click="abierto = !abierto" :aria-expanded="abierto"
                    class="presionable grid size-11 place-items-center rounded-full border-[1.5px] border-primario bg-primario/15 text-sm font-bold text-primario shadow-[0_0_0_4px_rgb(76_141_255/0.08)] transition hover:shadow-[0_0_0_6px_rgb(76_141_255/0.14)]"
                    aria-label="Abrir menú de cuenta">
                @if ($user->iniciales !== '')
                    {{ $user->iniciales }}
                @else
                    <x-icono nombre="usuario" />
                @endif
            </button>
            <div x-cloak x-show="abierto" @click.outside="abierto = false"
                 x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 scale-95 -translate-y-1" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition duration-100 ease-in" x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 z-30 mt-2 w-52 origin-top-right overflow-hidden rounded-2xl border border-borde bg-tarjeta-alta/95 py-1 shadow-2xl shadow-black/40 backdrop-blur-xl">
                <a href="{{ route('perfil') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold hover:bg-white/5">
                    <x-icono nombre="usuario" /> Ver perfil
                </a>
                <a href="{{ route('perfil.editar') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold hover:bg-white/5">
                    <x-icono nombre="editar" /> Editar datos
                </a>
                <a href="{{ route('entrenamientos.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold hover:bg-white/5">
                    <x-icono nombre="calendario" /> Historial
                </a>
                <form method="POST" action="{{ route('logout') }}" class="border-t border-borde">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-sm font-semibold text-error hover:bg-white/5">
                        <x-icono nombre="salir" /> Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </header>

    <x-aviso />

    {{-- Perfil sin completar: sin peso no hay calorías y sin días no hay
         calendario. Un llamado corto para completarlo. --}}
    @if ($perfilIncompleto)
        <a href="{{ route('perfil.editar') }}"
           class="aparecer tarjeta group mt-4 flex items-center gap-3 rounded-2xl border border-dashed border-primario/40 bg-primario/[0.06] px-3.5 py-3 transition hover:border-primario/70" style="--i: 1">
            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-primario/15 text-primario">
                <x-icono nombre="objetivo" class="size-5" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-sm font-semibold">Completa tu perfil</span>
                <span class="block text-xs text-tenue">Objetivo, nivel, peso y días: el coach te aconseja mejor.</span>
            </span>
            <x-icono nombre="flecha-der" class="size-4 text-primario transition group-hover:translate-x-0.5" />
        </a>
    @endif

    {{-- ===================== 2. Racha y meta de la semana ===================== --}}
    <x-tarjeta class="aparecer mt-4 p-4" style="--i: 1">
        <div class="flex items-center gap-2">
            <span @class([
                'grid size-8 place-items-center rounded-xl',
                'bg-fuego/15 text-fuego' => $racha['actual'] > 0,
                'bg-tarjeta-alta text-tenue' => $racha['actual'] === 0,
            ])>
                <x-icono nombre="llama" :class="$racha['actual'] > 0 ? 'llama-viva size-[18px]' : 'size-[18px]'" />
            </span>
            <p class="flex-1 text-sm font-bold tabular-nums" x-data x-contar.250="{{ $racha['actual'] }}">
                {{ $racha['actual'] }} {{ $racha['actual'] === 1 ? 'día seguido' : 'días seguidos' }}
            </p>
            <p class="flex items-center gap-1 rounded-full border border-borde bg-tarjeta-alta px-2.5 py-1 text-[11px] text-tenue">
                <x-icono nombre="trofeo" class="size-3.5" /> Récord {{ $racha['record'] }}
            </p>
        </div>

        {{-- Un cuadrito por día, de lunes a domingo. --}}
        <ol class="mt-4 grid grid-cols-7 gap-1.5">
            @foreach ($racha['dias'] as $dia)
                <li class="text-center" title="{{ $dia['nombre'] }}">
                    <span @class([
                        'mb-1.5 block text-[11px]',
                        'font-bold text-texto' => $dia['estado'] === 'hoy',
                        'text-tenue' => $dia['estado'] !== 'hoy',
                    ])>{{ $dia['inicial'] }}</span>
                    <span style="--retraso: {{ 150 + $loop->index * 45 }}ms" @class([
                        'crecer grid h-8 place-items-center rounded-[9px]',
                        'bg-gradient-to-b from-[#5b97ff] to-primario-fuerte text-white shadow-[0_6px_16px_-8px_rgb(47_107_255)]' => $dia['estado'] === 'entrenado',
                        'border-[1.5px] border-dashed border-texto/60 latir' => $dia['estado'] === 'hoy',
                        'bg-primario/10' => $dia['estado'] === 'descanso',
                        'border border-borde bg-tarjeta-alta' => $dia['estado'] === 'futuro',
                    ])>
                        @if ($dia['estado'] === 'entrenado')
                            <x-icono nombre="check" class="size-3.5" />
                        @endif
                    </span>
                    <span class="sr-only">{{ $dia['nombre'] }}: {{ $dia['estado'] }}</span>
                </li>
            @endforeach
        </ol>

        {{-- Meta semanal: sesiones hechas contra los días del perfil. --}}
        <div class="mt-4">
            <div class="flex items-baseline justify-between text-xs">
                <span class="text-tenue">Meta semanal</span>
                <span class="font-semibold tabular-nums">
                    {{ $hechas }} de {{ $meta }} {{ $meta === 1 ? 'sesión' : 'sesiones' }}
                    @if ($hechas >= $meta)
                        <span class="text-exito">· ¡cumplida!</span>
                    @endif
                </span>
            </div>
            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-tarjeta-alta" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $meta }}" aria-valuenow="{{ $hechas }}" aria-label="Meta semanal">
                <div x-data="{ ancho: 0 }" x-init="setTimeout(() => ancho = {{ $avanceMeta }}, 350)"
                     :style="`width: ${ancho}%`" style="width: {{ $avanceMeta }}%"
                     @class([
                         'h-full rounded-full transition-[width] duration-1000 ease-[cubic-bezier(.22,1,.36,1)]',
                         'bg-gradient-to-r from-primario to-ia' => $hechas < $meta,
                         'bg-gradient-to-r from-exito to-primario' => $hechas >= $meta,
                     ])></div>
            </div>
        </div>
    </x-tarjeta>

    {{-- ===================== 3. Números de la semana ===================== --}}
    <h2 class="sr-only">Esta semana</h2>
    <div class="mt-2 grid grid-cols-3 gap-2">
        <x-stat-card class="aparecer" style="--i: 2" icono="calendario"
                     :valor="$estaSemana['sesiones']" :contar="$estaSemana['sesiones']" :retraso="300"
                     etiqueta="Sesiones" />
        <x-stat-card class="aparecer" style="--i: 3" icono="pesa"
                     :valor="Formato::miles($estaSemana['volumen'])" :contar="round($estaSemana['volumen'])" :retraso="340"
                     unidad="kg" etiqueta="Volumen" />
        {{-- Las calorías se estiman con el peso del perfil; si aún no hay
             peso, se muestra el tiempo entrenado para no inventar un número. --}}
        @if ($estaSemana['kcal'] !== null)
            <x-stat-card class="aparecer" style="--i: 4" icono="llama"
                         :valor="Formato::miles($estaSemana['kcal'])" :contar="round($estaSemana['kcal'])" :retraso="380"
                         unidad="kcal" etiqueta="Quemadas" />
        @else
            <x-stat-card class="aparecer" style="--i: 4" icono="reloj"
                         :valor="Formato::minutos($estaSemana['minutos'])" etiqueta="Tiempo" />
        @endif
    </div>

    {{-- ===================== 4. Coach IA · Sugerencia de hoy ===================== --}}
    <x-tarjeta variante="ia" class="aparecer mt-3 p-4" style="--i: 5">
        <p class="etiqueta flex items-center gap-1.5 text-ia">
            <x-icono nombre="chispas" class="size-4" /> Coach IA · Sugerencia de hoy
        </p>

        @if ($siguiente)
            <p class="mt-2.5 text-[15px] leading-relaxed">{{ $sugerencia }}</p>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('entrenamientos.create', ['rutina' => $siguiente->id]) }}"
                   class="boton-primario inline-flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-semibold text-white">
                    <x-icono nombre="play" class="size-3.5" /> Empezar {{ $siguiente->nombre }}
                </a>
                <a href="{{ route('coach') }}"
                   class="presionable inline-flex items-center rounded-xl border border-ia-borde bg-white/[0.02] px-4 py-2.5 text-sm font-semibold text-texto transition hover:border-ia/60 hover:bg-white/5">
                    Preguntar al coach
                </a>
            </div>
        @else
            <p class="mt-2.5 font-display text-lg font-bold leading-tight">Arma tu primera rutina</p>
            <p class="mt-1 text-sm text-tenue">El coach te propone una según tu objetivo, tu nivel y el tiempo que tienes.</p>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('rutinas.generar') }}"
                   class="boton-primario boton-ia inline-flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-semibold text-white">
                    <x-icono nombre="chispas" class="size-4" /> Generar rutina
                </a>
                <a href="{{ route('coach') }}"
                   class="presionable inline-flex items-center rounded-xl border border-ia-borde px-4 py-2.5 text-sm font-semibold text-texto transition hover:bg-white/5">
                    Preguntar al coach
                </a>
            </div>
        @endif
    </x-tarjeta>

    {{-- ===================== 5. Últimas sesiones ===================== --}}
    <x-titulo-seccion texto="Últimas sesiones" accion="Ver todas" :href="route('entrenamientos.index')"
                      class="aparecer mt-7" style="--i: 6" />

    @forelse ($ultimas as $sesion)
        <x-tarjeta :href="route('entrenamientos.index').'#sesion-'.$sesion->id" class="aparecer mt-2 flex items-center gap-3 px-3 py-2.5"
                   style="--i: {{ min(7 + $loop->index, 9) }}">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-primario/25 to-primario/5 font-display text-sm font-bold text-primario">
                {{ mb_strtoupper(mb_substr($sesion->nombre, 0, 1)) }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">{{ $sesion->titulo }}</p>
                <p class="text-xs text-tenue">
                    {{ Formato::diaRelativo($sesion->inicio, $hoy) }} · {{ Formato::minutos($sesion->duracion_minutos) }}
                </p>
            </div>
            <p class="text-xs font-medium tabular-nums text-tenue">
                @if ($sesion->volumen > 0)
                    {{ Formato::miles($sesion->volumen) }} kg
                @elseif ($user->peso_kg)
                    {{ Formato::miles($sesion->kcalEstimadas($user->peso_kg)) }} kcal
                @else
                    {{ $sesion->series->count() }} series
                @endif
            </p>
            <x-icono nombre="flecha-der" class="size-4 text-tenue/60" />
        </x-tarjeta>
    @empty
        <div class="aparecer mt-2 rounded-2xl border border-dashed border-borde px-4 py-8 text-center" style="--i: 7">
            <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-primario/10 text-primario animate-flotar">
                <x-icono nombre="pesa" class="size-6" />
            </span>
            <p class="mt-3 text-sm text-tenue">Aún no terminas ningún entrenamiento.</p>
            <a href="{{ route('entrenamientos.create') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-primario hover:underline">
                Registrar el primero <x-icono nombre="flecha-der" class="size-4" />
            </a>
        </div>
    @endforelse
</x-app-layout>
