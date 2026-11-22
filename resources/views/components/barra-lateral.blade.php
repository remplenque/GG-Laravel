{{-- Barra lateral para pantallas grandes (lg): la marca, los mismos
     destinos de la barra inferior más Historial y Biblioteca, y la cuenta
     abajo. En el celular no se dibuja (hidden). --}}
@php
    $user = auth()->user();
    $destinos = [
        ['ruta' => 'inicio', 'activa' => 'inicio', 'icono' => 'inicio', 'etiqueta' => 'Inicio'],
        ['ruta' => 'rutinas.index', 'activa' => 'rutinas.*', 'icono' => 'lista', 'etiqueta' => 'Rutinas'],
        ['ruta' => 'entrenamientos.index', 'activa' => ['entrenamientos.index', 'entrenamientos.edit'], 'icono' => 'calendario', 'etiqueta' => 'Historial'],
        ['ruta' => 'ejercicios.index', 'activa' => 'ejercicios.*', 'icono' => 'libro', 'etiqueta' => 'Biblioteca'],
        ['ruta' => 'coach', 'activa' => 'coach', 'icono' => 'chispas', 'etiqueta' => 'Coach IA', 'ia' => true],
        ['ruta' => 'perfil', 'activa' => 'perfil*', 'icono' => 'usuario', 'etiqueta' => 'Perfil'],
    ];
@endphp

<aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-borde/80 bg-fondo/70 px-4 py-6 backdrop-blur-xl lg:flex"
       style="view-transition-name: barra-lateral" aria-label="Navegación principal">
    <a href="{{ route('inicio') }}" class="flex items-center gap-3 px-2">
        <span class="boton-primario grid size-10 place-items-center rounded-xl font-display text-base font-bold text-white">GG</span>
        <span>
            <span class="block font-display text-xl font-bold leading-none">GO <span class="text-primario">GYM</span></span>
            <span class="mt-1 block text-[9px] tracking-[0.3em] text-tenue">ENTRENA · PROGRESA</span>
        </span>
    </a>

    <a href="{{ route('entrenamientos.create') }}"
       class="boton-primario mt-8 flex h-11 items-center justify-center gap-2 rounded-xl text-sm font-semibold text-white">
        <x-icono nombre="mas" class="size-4" /> Registrar entrenamiento
    </a>

    <ul class="mt-6 space-y-1">
        @foreach ($destinos as $destino)
            @php($activa = request()->routeIs(...(array) $destino['activa']))
            <li>
                <a href="{{ route($destino['ruta']) }}" @if ($activa) aria-current="page" @endif
                   @class([
                       'relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors',
                       'font-semibold' => $activa,
                       'text-primario' => $activa && empty($destino['ia']),
                       'text-ia' => $activa && ! empty($destino['ia']),
                       'text-tenue hover:bg-white/[0.04] hover:text-texto' => ! $activa,
                   ])>
                    @if ($activa)
                        <span @class([
                            'absolute inset-0 rounded-xl border',
                            'border-primario/25 bg-primario/10' => empty($destino['ia']),
                            'border-ia/25 bg-ia/10' => ! empty($destino['ia']),
                        ]) style="view-transition-name: indicador-lateral"></span>
                    @endif
                    <x-icono :nombre="$destino['icono']" class="relative size-5" />
                    <span class="relative">{{ $destino['etiqueta'] }}</span>
                    @if (! empty($destino['ia']))
                        <span class="relative ml-auto rounded-full bg-ia/15 px-1.5 py-0.5 text-[9px] font-bold tracking-wider text-ia">IA</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>

    @if ($user)
        <div class="mt-auto flex items-center gap-3 rounded-2xl border border-borde bg-tarjeta/70 p-3">
            <span class="grid size-9 shrink-0 place-items-center rounded-full border border-primario/60 bg-primario/15 text-xs font-bold text-primario">
                {{ $user->iniciales ?: '?' }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">{{ trim($user->name.' '.$user->apellido) }}</p>
                <p class="truncate text-[11px] text-tenue">{{ $user->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="grid size-8 place-items-center rounded-lg text-tenue transition hover:bg-error/10 hover:text-error" aria-label="Cerrar sesión" title="Cerrar sesión">
                    <x-icono nombre="salir" class="size-4" />
                </button>
            </form>
        </div>
    @endif
</aside>
