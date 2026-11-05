{{-- Barra inferior del mockup: Inicio, Rutinas, + (registrar), Coach IA y
     Perfil. La pestaña activa va en azul; Coach IA en violeta. --}}
@php
    $destinos = [
        ['ruta' => 'inicio', 'activa' => 'inicio', 'icono' => 'inicio', 'etiqueta' => 'Inicio'],
        ['ruta' => 'rutinas.index', 'activa' => 'rutinas.*', 'icono' => 'lista', 'etiqueta' => 'Rutinas'],
        ['ruta' => 'entrenamientos.create', 'activa' => 'entrenamientos.*', 'icono' => 'mas', 'etiqueta' => 'Registrar', 'central' => true],
        ['ruta' => 'coach', 'activa' => 'coach', 'icono' => 'chispas', 'etiqueta' => 'Coach IA', 'ia' => true],
        ['ruta' => 'perfil', 'activa' => 'perfil', 'icono' => 'usuario', 'etiqueta' => 'Perfil'],
    ];
@endphp

<nav class="fixed inset-x-0 bottom-0 z-20 mx-auto max-w-md border-t border-borde bg-fondo/95 backdrop-blur pb-seguro" aria-label="Navegación principal">
    <ul class="grid h-16 grid-cols-5">
        @foreach ($destinos as $destino)
            @php($activa = request()->routeIs($destino['activa']))
            <li>
                <a href="{{ route($destino['ruta']) }}"
                   @if ($activa) aria-current="page" @endif
                   @class([
                       'flex h-full flex-col items-center justify-center gap-1 text-[11px] transition-colors',
                       'font-bold' => $activa,
                       'text-primario' => $activa && empty($destino['ia']),
                       'text-ia' => $activa && ! empty($destino['ia']),
                       'text-tenue hover:text-texto' => ! $activa,
                   ])>
                    @if (! empty($destino['central']))
                        <span class="-mt-5 grid size-12 place-items-center rounded-full bg-primario-fuerte text-white shadow-lg shadow-primario/30 ring-4 ring-fondo">
                            <x-icono :nombre="$destino['icono']" class="size-6" />
                        </span>
                        <span class="sr-only">{{ $destino['etiqueta'] }}</span>
                    @else
                        <x-icono :nombre="$destino['icono']" class="size-5" />
                        {{ $destino['etiqueta'] }}
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
</nav>
