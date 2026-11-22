{{-- Barra inferior del mockup (solo en el celular): Inicio, Rutinas, +
     (registrar), Coach IA y Perfil. La pestaña activa lleva una píldora de
     color detrás del ícono; con las transiciones de página, la píldora se
     desliza de una pestaña a la otra (view-transition-name). --}}
@php
    $destinos = [
        ['ruta' => 'inicio', 'activa' => 'inicio', 'icono' => 'inicio', 'etiqueta' => 'Inicio'],
        ['ruta' => 'rutinas.index', 'activa' => ['rutinas.*', 'ejercicios.*'], 'icono' => 'lista', 'etiqueta' => 'Rutinas'],
        ['ruta' => 'entrenamientos.create', 'activa' => 'entrenamientos.*', 'icono' => 'mas', 'etiqueta' => 'Registrar', 'central' => true],
        ['ruta' => 'coach', 'activa' => 'coach', 'icono' => 'chispas', 'etiqueta' => 'Coach IA', 'ia' => true],
        ['ruta' => 'perfil', 'activa' => 'perfil*', 'icono' => 'usuario', 'etiqueta' => 'Perfil'],
    ];
@endphp

<nav class="fixed inset-x-0 bottom-0 z-20 mx-auto max-w-md border-t border-borde/80 bg-fondo/80 backdrop-blur-xl pb-seguro sm:rounded-t-3xl sm:border-x lg:hidden"
     style="view-transition-name: barra-nav" aria-label="Navegación principal">
    <ul class="grid h-16 grid-cols-5">
        @foreach ($destinos as $destino)
            @php($activa = request()->routeIs(...(array) $destino['activa']))
            <li>
                <a href="{{ route($destino['ruta']) }}"
                   @if ($activa) aria-current="page" @endif
                   @class([
                       'presionable group flex h-full flex-col items-center justify-center gap-1 text-[11px] transition-colors',
                       'font-bold' => $activa,
                       'text-primario' => $activa && empty($destino['ia']),
                       'text-ia' => $activa && ! empty($destino['ia']),
                       'text-tenue hover:text-texto' => ! $activa,
                   ])>
                    @if (! empty($destino['central']))
                        <span @class([
                            'boton-primario -mt-6 grid size-[52px] place-items-center rounded-2xl text-white ring-4 ring-fondo transition-transform group-hover:-translate-y-0.5',
                            'rotate-45' => $activa,
                        ])>
                            <x-icono :nombre="$destino['icono']" :class="$activa ? 'size-6 -rotate-45 transition-transform' : 'size-6 transition-transform'" />
                        </span>
                        <span class="sr-only">{{ $destino['etiqueta'] }}</span>
                    @else
                        <span class="relative grid h-7 w-12 place-items-center">
                            @if ($activa)
                                <span @class([
                                    'absolute inset-0 rounded-full',
                                    'bg-primario/15' => empty($destino['ia']),
                                    'bg-ia/15' => ! empty($destino['ia']),
                                ]) style="view-transition-name: indicador-nav"></span>
                            @endif
                            <x-icono :nombre="$destino['icono']" class="relative size-5 transition-transform group-active:scale-90" />
                        </span>
                        {{ $destino['etiqueta'] }}
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
</nav>
