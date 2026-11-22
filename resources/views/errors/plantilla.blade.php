{{-- Página de error con el diseño de Go Gym. Cada error (404, 419…) la
     extiende con su código, título, texto e ícono. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-head :titulo="$titulo" />
    </head>
    <body>
        <main class="mx-auto flex min-h-dvh max-w-md flex-col items-center justify-center px-6 text-center">
            <div class="aparecer relative" style="--i: 0">
                <span class="absolute inset-0 rounded-full bg-primario/20 blur-2xl"></span>
                <span class="relative grid size-20 place-items-center rounded-3xl border border-borde bg-tarjeta text-primario animate-flotar">
                    <x-icono :nombre="$icono" class="size-9" />
                </span>
            </div>
            <p class="aparecer mt-6 font-display text-6xl font-bold texto-marca" style="--i: 1">{{ $codigo }}</p>
            <h1 class="aparecer mt-2 font-display text-xl font-bold" style="--i: 2">{{ $titulo }}</h1>
            <p class="aparecer mt-2 text-sm leading-relaxed text-tenue" style="--i: 3">{{ $texto }}</p>
            <div class="aparecer mt-8 flex flex-wrap justify-center gap-2" style="--i: 4">
                <a href="{{ url('/') }}" class="boton-primario inline-flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold text-white">
                    <x-icono nombre="inicio" class="size-4" /> Ir al inicio
                </a>
                <button type="button" onclick="history.length > 1 ? history.back() : location.assign('/')"
                        class="presionable rounded-xl border border-borde bg-tarjeta px-5 py-3 text-sm font-semibold transition hover:border-primario/60">
                    Volver
                </button>
            </div>
        </main>
    </body>
</html>
