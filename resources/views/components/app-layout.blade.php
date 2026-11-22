{{-- Marco de las pantallas con sesión.

     En el celular: una columna de ancho de teléfono y, si $navegacion, la
     barra inferior. En pantallas grandes (lg): barra lateral fija a la
     izquierda y una columna más ancha. $pie va fijo abajo (p. ej. "Empezar
     rutina") y reemplaza a la barra inferior. --}}
@props(['titulo' => null, 'navegacion' => true])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-head :titulo="$titulo" />
    </head>
    <body>
        <a href="#contenido"
           class="sr-only z-[110] rounded-xl bg-primario-fuerte px-4 py-2 text-sm font-semibold text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4">
            Saltar al contenido
        </a>

        <x-barra-lateral />

        <div class="lg:pl-64">
            <div class="mx-auto min-h-dvh max-w-md lg:max-w-2xl">
                <main id="contenido" @class([
                    'px-4 pt-4 lg:px-8 lg:pt-8',
                    'pb-32' => isset($pie),
                    'pb-28 lg:pb-12' => $navegacion && ! isset($pie),
                    'pb-10' => ! $navegacion && ! isset($pie),
                ])>
                    {{ $slot }}
                </main>

                @isset($pie)
                    <div class="fixed inset-x-0 bottom-0 z-20 border-t border-borde/80 bg-fondo/85 backdrop-blur-xl pb-seguro lg:left-64"
                         style="view-transition-name: pie-fijo">
                        <div class="mx-auto max-w-md px-4 pt-3 pb-3 lg:max-w-2xl lg:px-8">{{ $pie }}</div>
                    </div>
                @elseif ($navegacion)
                    <x-barra-navegacion />
                @endisset
            </div>
        </div>
    </body>
</html>
