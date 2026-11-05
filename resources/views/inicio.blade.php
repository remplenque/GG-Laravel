{{-- Vista 01 · Inicio: racha, totales de la semana, la rutina que toca y las
     últimas sesiones. Los datos llegan desde InicioController. --}}
@use('App\Support\Formato')

<x-app-layout titulo="Inicio">
    {{-- Saludo y menú de cuenta --}}
    <header class="flex items-center gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-xs text-tenue">{{ Formato::fechaLarga($hoy) }}</p>
            <h1 class="truncate font-display text-2xl font-bold">
                {{ $user->name ? 'Hola, '.$user->name : 'Hola' }}
            </h1>
        </div>

        <div class="relative" x-data="{ abierto: false }" @keydown.escape.window="abierto = false">
            <button type="button" @click="abierto = !abierto" :aria-expanded="abierto"
                    class="grid size-10 place-items-center rounded-full border-[1.5px] border-primario bg-primario/15 text-sm font-bold text-primario"
                    aria-label="Abrir menú de cuenta">
                @if ($user->iniciales !== '')
                    {{ $user->iniciales }}
                @else
                    <x-icono nombre="usuario" />
                @endif
            </button>
            <div x-cloak x-show="abierto" x-transition.origin.top.right @click.outside="abierto = false"
                 class="absolute right-0 z-30 mt-2 w-48 overflow-hidden rounded-xl border border-borde bg-tarjeta-alta py-1 shadow-xl">
                <a href="{{ route('perfil') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold hover:bg-white/5">
                    <x-icono nombre="usuario" /> Ver perfil
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

    {{-- Racha --}}
    <x-tarjeta class="mt-4">
        <div class="flex items-center gap-1.5">
            <x-icono nombre="llama" :class="$racha['actual'] > 0 ? 'text-primario' : 'text-tenue'" />
            <p class="flex-1 text-sm font-bold">
                {{ $racha['actual'] }} {{ $racha['actual'] === 1 ? 'día seguido' : 'días seguidos' }}
            </p>
            <p class="text-xs text-tenue">Récord {{ $racha['record'] }}</p>
        </div>
        <ol class="mt-3 grid grid-cols-7 gap-1.5">
            @foreach ($racha['dias'] as $dia)
                <li class="text-center" title="{{ $dia['nombre'] }}">
                    <span @class([
                        'block aspect-[1.15] rounded-[7px]',
                        'bg-primario' => $dia['estado'] === 'entrenado',
                        'border border-dashed border-tenue' => $dia['estado'] === 'hoy',
                        'bg-primario/12' => $dia['estado'] === 'descanso',
                        'border border-borde' => $dia['estado'] === 'futuro',
                    ])></span>
                    <span @class([
                        'mt-1 block text-[11px]',
                        'text-texto' => $dia['estado'] === 'entrenado',
                        'text-tenue' => $dia['estado'] !== 'entrenado',
                    ])>{{ $dia['inicial'] }}</span>
                    <span class="sr-only">{{ $dia['nombre'] }}: {{ $dia['estado'] }}</span>
                </li>
            @endforeach
        </ol>
    </x-tarjeta>

    {{-- Esta semana --}}
    <x-titulo-seccion texto="Esta semana" class="mt-5" />
    <div class="mt-2 grid grid-cols-3 gap-2">
        <x-stat-card :valor="$estaSemana['sesiones']" etiqueta="Sesiones" />
        <x-stat-card :valor="Formato::miles($estaSemana['volumen'])" unidad="kg" etiqueta="Volumen" />
        <x-stat-card :valor="Formato::minutos($estaSemana['minutos'])" etiqueta="Tiempo" />
    </div>

    {{-- La rutina que toca --}}
    @if ($siguiente)
        <x-tarjeta variante="destacada" class="mt-3 flex items-center gap-3 p-4">
            <div class="min-w-0 flex-1">
                <p class="etiqueta text-primario">{{ $etiquetaSiguiente }}</p>
                <p class="mt-1.5 font-bold leading-tight">{{ $siguiente->titulo }}</p>
                <p class="mt-1 text-xs text-tenue">{{ $siguiente->resumen }}</p>
            </div>
            <a href="{{ route('entrenamientos.create', ['rutina' => $siguiente->id]) }}"
               class="inline-flex items-center gap-1 rounded-[10px] border-[1.5px] border-primario px-4 py-2 text-sm font-semibold text-primario hover:bg-primario/10">
                <x-icono nombre="play" class="size-4" /> Empezar
            </a>
        </x-tarjeta>
    @else
        <x-tarjeta variante="destacada" class="mt-3 flex items-center gap-3 p-4">
            <div class="min-w-0 flex-1">
                <p class="etiqueta text-primario">Sin rutinas</p>
                <p class="mt-1.5 font-bold leading-tight">Arma tu primera rutina</p>
                <p class="mt-1 text-xs text-tenue">El coach te propone una según tu objetivo y tu tiempo.</p>
            </div>
            <a href="{{ route('rutinas.generar') }}"
               class="inline-flex items-center gap-1 rounded-[10px] border-[1.5px] border-primario px-4 py-2 text-sm font-semibold text-primario hover:bg-primario/10">
                Crear
            </a>
        </x-tarjeta>
    @endif

    {{-- Acceso al Coach IA. La sugerencia del día (IA) irá en esta tarjeta. --}}
    <x-tarjeta variante="ia" class="mt-3">
        <p class="etiqueta flex items-center gap-1.5 text-ia"><x-icono nombre="chispas" class="size-4" /> Coach IA</p>
        <p class="mt-2 text-sm">Pregúntale cómo hacer un ejercicio, cuánto descansar o con qué reemplazarlo.</p>
        <a href="{{ route('coach') }}" class="mt-3 inline-flex rounded-[10px] border border-ia-borde px-4 py-2 text-sm font-semibold text-texto hover:bg-white/5">
            Preguntar al coach
        </a>
    </x-tarjeta>

    {{-- Últimas sesiones --}}
    <x-titulo-seccion texto="Últimas sesiones" accion="Ver todas" :href="route('entrenamientos.index')" class="mt-5" />
    @forelse ($ultimas as $sesion)
        <x-tarjeta :href="route('entrenamientos.index')" class="mt-2 flex items-center gap-3 px-3 py-2.5">
            <span class="grid size-9 shrink-0 place-items-center rounded-[10px] bg-primario/14 text-sm font-bold text-primario">
                {{ mb_strtoupper(mb_substr($sesion->nombre, 0, 1)) }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">{{ $sesion->titulo }}</p>
                <p class="text-xs text-tenue">
                    {{ Formato::diaRelativo($sesion->inicio, $hoy) }} · {{ Formato::minutos($sesion->duracion_minutos) }}
                </p>
            </div>
            <p class="text-xs text-tenue">
                @if ($sesion->volumen > 0)
                    {{ Formato::miles($sesion->volumen) }} kg
                @elseif ($user->peso_kg)
                    {{ Formato::miles($sesion->kcalEstimadas($user->peso_kg)) }} kcal
                @else
                    {{ $sesion->series->count() }} series
                @endif
            </p>
        </x-tarjeta>
    @empty
        <p class="py-4 text-center text-sm text-tenue">Aún no terminas ningún entrenamiento.</p>
    @endforelse
</x-app-layout>
