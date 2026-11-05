{{-- Marco de las pantallas con sesión: columna de ancho de celular y, si
     $navegacion, la barra inferior. $pie va fijo abajo (p. ej. "Empezar rutina"). --}}
@props(['titulo' => null, 'navegacion' => true])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-head :titulo="$titulo" />
    </head>
    <body>
        <div class="mx-auto min-h-dvh max-w-md border-borde sm:border-x">
            <main @class(['px-4 pt-4', 'pb-28' => $navegacion || isset($pie), 'pb-8' => ! $navegacion && ! isset($pie)])>
                {{ $slot }}
            </main>

            @isset($pie)
                <div class="fixed inset-x-0 bottom-0 z-20 mx-auto max-w-md border-t border-borde bg-fondo/95 px-4 pt-3 backdrop-blur pb-seguro">
                    <div class="pb-3">{{ $pie }}</div>
                </div>
            @elseif ($navegacion)
                <x-barra-navegacion />
            @endisset
        </div>
    </body>
</html>
