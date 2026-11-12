@use('App\Support\Formato')

<x-app-layout titulo="Perfil">
    <x-encabezado titulo="Perfil" subtitulo="Tu cuenta y plan de entrenamiento" class="aparecer" style="--i: 0" />

    <x-tarjeta class="aparecer mt-4 overflow-hidden bg-gradient-to-br from-primario/15 to-tarjeta p-4" style="--i: 1">
        <div class="flex items-center gap-4">
            <div class="grid size-16 shrink-0 place-items-center rounded-2xl border border-primario/50 bg-primario/15 font-display text-xl font-bold text-primario shadow-lg shadow-primario/10">
                {{ $user->iniciales ?: '?' }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate font-display text-xl font-bold">{{ trim($user->name.' '.$user->apellido) ?: 'Tu perfil' }}</p>
                <p class="truncate text-sm text-tenue">{{ $user->email }}</p>
                @if ($user->nivel)
                    <span class="mt-2 inline-flex rounded-full border border-primario/30 bg-primario/10 px-2.5 py-1 text-[11px] font-bold text-primario">Nivel {{ $user->nivel }}</span>
                @endif
            </div>
        </div>
    </x-tarjeta>

    <x-titulo-seccion texto="Próximos entrenamientos" accion="Ver rutinas" :href="route('rutinas.index')"
                      class="aparecer mt-6" style="--i: 2" />
    <div class="mt-2 space-y-2">
        @forelse ($proximos as $proximo)
            @php
                $fecha = $proximo['fecha'];
                $rutina = $proximo['rutina'];
                $dia = $fecha->isSameDay($hoy)
                    ? 'Hoy'
                    : ($fecha->isSameDay($hoy->addDay()) ? 'Mañana' : Formato::nombreDia($fecha));
            @endphp

            <x-tarjeta class="aparecer flex items-center gap-3 px-3 py-3" style="--i: {{ 3 + $loop->index }}">
                <div @class([
                    'grid size-12 shrink-0 place-items-center rounded-xl border text-center',
                    'border-primario/60 bg-primario/15 text-primario' => $loop->first,
                    'border-borde bg-tarjeta-alta text-tenue' => ! $loop->first,
                ])>
                    <span class="block text-[10px] font-bold uppercase leading-none">{{ $fecha->isoFormat('MMM') }}</span>
                    <span class="block text-lg font-bold leading-none">{{ $fecha->day }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-primario">{{ $dia }} · {{ $user->hora_entrenamiento ? substr($user->hora_entrenamiento, 0, 5) : 'Horario libre' }}</p>
                    <p class="truncate text-sm font-bold">{{ $rutina?->nombre ?? 'Sesión por definir' }}</p>
                    <p class="truncate text-xs text-tenue">{{ $rutina?->resumen ?? 'Crea una rutina para completar tu calendario' }}</p>
                </div>
                @if ($rutina)
                    <a href="{{ route('entrenamientos.create', ['rutina' => $rutina->id]) }}"
                       class="grid size-9 shrink-0 place-items-center rounded-xl bg-primario-fuerte text-white hover:bg-primario"
                       aria-label="Empezar {{ $rutina->nombre }}">
                        <x-icono nombre="play" class="size-3.5" />
                    </a>
                @else
                    <a href="{{ route('rutinas.generar') }}" class="text-primario" aria-label="Crear rutina"><x-icono nombre="flecha-der" /></a>
                @endif
            </x-tarjeta>
        @empty
            <x-tarjeta class="aparecer mt-2 text-center" style="--i: 3">
                <x-icono nombre="calendario" class="mx-auto size-7 text-tenue" />
                <p class="mt-2 text-sm font-bold">Aún no tienes días programados</p>
                <p class="mt-1 text-xs text-tenue">Agrega días de entrenamiento a tu perfil para ver tu calendario.</p>
            </x-tarjeta>
        @endforelse
    </div>

    <x-titulo-seccion texto="Tu progreso este mes" class="aparecer mt-6" style="--i: 6" />
    <div class="mt-2 grid grid-cols-3 gap-2">
        <x-stat-card class="aparecer" style="--i: 7" :valor="$progreso['sesiones']" etiqueta="Sesiones" />
        <x-stat-card class="aparecer" style="--i: 8" :valor="Formato::minutos($progreso['minutos'])" etiqueta="Entrenando" />
        <x-stat-card class="aparecer" style="--i: 9" :valor="$progreso['racha']" unidad="días" etiqueta="Racha actual" />
    </div>

    <x-titulo-seccion texto="Datos personales" class="aparecer mt-6" style="--i: 10" />
    <x-tarjeta class="aparecer mt-2 divide-y divide-borde !p-0" style="--i: 11">
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
            @php($nombresDias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'])
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

    <form method="POST" action="{{ route('logout') }}" class="aparecer mt-4" style="--i: 12">
        @csrf
        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl border border-error/30 bg-error/5 px-4 py-3 text-sm font-semibold text-error transition hover:bg-error/10">
            <x-icono nombre="salir" /> Cerrar sesión
        </button>
    </form>
</x-app-layout>
