{{-- Marco de las pantallas sin sesión (login, registro, recuperar clave).
     En el celular: la marca arriba y la tarjeta del formulario. En pantallas
     grandes: a la izquierda la propuesta de valor con una vista previa de la
     app, a la derecha el formulario. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-head />
    </head>
    <body>
        <div class="mx-auto grid min-h-dvh max-w-6xl lg:grid-cols-[1.1fr_1fr] lg:gap-12 lg:px-10">
            {{-- ===================== Propuesta (solo lg) ===================== --}}
            <section class="relative hidden flex-col justify-center py-16 lg:flex" aria-hidden="true">
                <div class="pointer-events-none absolute left-10 top-1/4 size-72 rounded-full bg-primario/20 blur-3xl"></div>
                <div class="pointer-events-none absolute bottom-1/4 right-0 size-64 rounded-full bg-ia/15 blur-3xl"></div>

                <p class="aparecer etiqueta relative text-primario" style="--i: 1">Tu entrenador de bolsillo</p>
                <h2 class="aparecer relative mt-3 font-display text-5xl font-bold leading-[1.05]" style="--i: 2">
                    Entrena con un plan.<br><span class="texto-marca">Progresa con datos.</span>
                </h2>
                <p class="aparecer relative mt-5 max-w-md text-base leading-relaxed text-tenue" style="--i: 3">
                    Registra tus series, sigue tu racha y deja que el Coach IA te arme rutinas según tu objetivo, tu nivel y tu tiempo.
                </p>

                <ul class="relative mt-8 space-y-3 text-sm">
                    @foreach ([
                        ['chispas', 'Coach IA que responde tus dudas de técnica', 'ia'],
                        ['lista', 'Rutinas personalizadas en segundos', 'primario'],
                        ['cronometro', 'Registro en vivo con temporizador de descanso', 'primario'],
                        ['grafico', 'Racha, récords y volumen semana a semana', 'primario'],
                    ] as $i => [$icono, $texto, $color])
                        <li class="aparecer flex items-center gap-3" style="--i: {{ 4 + $i }}">
                            <span class="grid size-9 place-items-center rounded-xl {{ $color === 'ia' ? 'bg-ia/15 text-ia' : 'bg-primario/15 text-primario' }}">
                                <x-icono :nombre="$icono" class="size-4" />
                            </span>
                            {{ $texto }}
                        </li>
                    @endforeach
                </ul>

                {{-- Mini vista previa de la racha, como en Inicio. --}}
                <div class="aparecer relative mt-10 w-80 rotate-[-2deg] rounded-2xl border border-borde bg-tarjeta/90 p-4 shadow-2xl shadow-black/40 animate-flotar" style="--i: 9">
                    <div class="flex items-center gap-2 text-sm font-bold">
                        <span class="grid size-7 place-items-center rounded-lg bg-fuego/15 text-fuego"><x-icono nombre="llama" class="llama-viva size-4" /></span>
                        12 días seguidos
                        <span class="ml-auto text-[11px] font-normal text-tenue">Récord 21</span>
                    </div>
                    <div class="mt-3 grid grid-cols-7 gap-1.5">
                        @foreach (['L', 'M', 'M', 'J', 'V', 'S', 'D'] as $i => $letra)
                            <div class="text-center">
                                <span class="block text-[10px] text-tenue">{{ $letra }}</span>
                                <span style="--retraso: {{ 600 + $i * 60 }}ms" @class([
                                    'crecer mt-1 block h-6 rounded-md',
                                    'bg-gradient-to-b from-[#5b97ff] to-primario-fuerte' => $i < 5,
                                    'border border-dashed border-texto/60' => $i === 5,
                                    'border border-borde bg-tarjeta-alta' => $i === 6,
                                ])></span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ===================== Formulario ===================== --}}
            <main class="flex flex-col justify-center px-6 py-10 sm:mx-auto sm:w-full sm:max-w-md lg:max-w-none lg:px-0">
                <a href="{{ route('login') }}" class="aparecer flex items-center gap-3" style="--i: 0">
                    <span class="boton-primario grid size-14 place-items-center rounded-2xl font-display text-xl font-bold text-white">GG</span>
                    <span>
                        <span class="block font-display text-3xl font-bold leading-none">GO <span class="text-primario">GYM</span></span>
                        <span class="mt-1.5 block text-[10px] tracking-[0.35em] text-tenue">ENTRENA · REGISTRA · PROGRESA</span>
                    </span>
                </a>

                <div class="aparecer tarjeta mt-8 rounded-3xl border border-borde bg-tarjeta/85 p-6 backdrop-blur-xl" style="--i: 1">
                    {{ $slot }}
                </div>

                <p class="aparecer mt-6 text-center text-[11px] text-tenue" style="--i: 2">
                    IIP323W · Proyecto Integrador · UDD 2026
                </p>
            </main>
        </div>
    </body>
</html>
