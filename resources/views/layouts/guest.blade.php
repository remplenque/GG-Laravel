<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-head />
    </head>
    <body>
        <div class="mx-auto flex min-h-dvh max-w-md flex-col justify-center px-6 py-10">
            <a href="{{ route('login') }}" class="flex items-center gap-3">
                <span class="grid size-14 place-items-center rounded-2xl bg-primario-fuerte font-display text-xl font-bold text-white">GG</span>
                <span>
                    <span class="block font-display text-3xl font-bold leading-none">GO <span class="text-primario">GYM</span></span>
                    <span class="mt-1.5 block text-[10px] tracking-[0.35em] text-tenue">ENTRENA · REGISTRA · PROGRESA</span>
                </span>
            </a>

            <div class="mt-8 rounded-2xl border border-borde bg-tarjeta p-5">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
