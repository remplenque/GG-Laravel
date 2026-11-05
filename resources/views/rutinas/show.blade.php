{{-- Detalle de una rutina: portada, grupos musculares (tocar uno resalta sus
     ejercicios) y los ejercicios, que se despliegan para ver cómo se hacen. --}}
<x-app-layout :titulo="$rutina->nombre" :navegacion="false">
    <div x-data="{ grupo: null, abierto: null }">
        <div class="relative -mx-4 -mt-4">
            <x-foto-rutina :categoria="$rutina->categoria" class="h-48 [&>svg]:size-10" />
            <div class="absolute inset-0 bg-gradient-to-b from-transparent from-35% to-fondo"></div>
            <a href="{{ route('rutinas.index') }}" class="absolute left-4 top-4 grid size-10 place-items-center rounded-full bg-fondo/60 backdrop-blur" aria-label="Volver">
                <x-icono nombre="flecha-izq" />
            </a>
            <div class="absolute inset-x-4 bottom-3">
                <p class="etiqueta text-primario">
                    {{ collect(['Rutina', $rutina->categoria ?: null])->filter()->join(' · ') }}
                </p>
                <h1 class="mt-1 font-display text-3xl font-bold leading-tight">{{ $rutina->nombre }}</h1>
                <p class="mt-1 text-xs text-tenue">{{ $rutina->resumen }}</p>
            </div>
        </div>

        @if ($rutina->grupos)
            <ul class="-mx-4 mt-3 flex gap-2 overflow-x-auto px-4 pb-1">
                @foreach ($rutina->grupos as $grupo)
                    <li>
                        <button type="button" @click="grupo = grupo === @js($grupo) ? null : @js($grupo)"
                                :class="grupo === @js($grupo) ? 'border-primario bg-primario/18 text-primario font-bold' : 'border-borde text-tenue'"
                                class="whitespace-nowrap rounded-full border px-3 py-1.5 text-xs transition">
                            {{ $grupo }}
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-titulo-seccion texto="Ejercicios" class="mt-4" />

        <ol class="mt-2 space-y-2">
            @forelse ($rutina->ejercicios as $i => $item)
                @php($ejercicio = $item->ejercicio)
                <li class="rounded-2xl border border-borde bg-tarjeta transition-opacity"
                    :class="grupo && grupo !== @js($ejercicio->grupo) && 'opacity-40'">
                    <button type="button" class="flex w-full items-center gap-3 p-3 text-left"
                            @click="abierto = abierto === {{ $item->id }} ? null : {{ $item->id }}"
                            :aria-expanded="abierto === {{ $item->id }}">
                        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-primario/15 text-xs font-bold text-primario">{{ $i + 1 }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold">{{ $ejercicio->nombre }}</span>
                            <span class="block text-xs text-tenue">
                                {{ collect([$item->resumen, $item->descanso])->filter()->join(' · ') }}
                            </span>
                        </span>
                        <span class="text-[11px] text-tenue">{{ $ejercicio->grupo }}</span>
                        <x-icono nombre="flecha-der" class="size-4 text-tenue transition-transform" x-bind:class="abierto === {{ $item->id }} && 'rotate-90'" />
                    </button>

                    <div x-cloak x-show="abierto === {{ $item->id }}" x-transition.opacity class="border-t border-borde px-3 pb-3 pt-3">
                        @if ($item->nota)
                            <p class="mb-3 rounded-lg bg-ia-fondo px-3 py-2 text-xs text-texto">{{ $item->nota }}</p>
                        @endif
                        <div class="flex gap-3">
                            @if ($ejercicio->url_imagen)
                                <img src="{{ $ejercicio->url_imagen }}" alt="{{ $ejercicio->nombre }}" loading="lazy"
                                     class="size-24 shrink-0 rounded-xl bg-white object-contain">
                            @endif
                            <div class="min-w-0 text-xs text-tenue">
                                <p>{{ $ejercicio->equipo }} · {{ $ejercicio->nivel }}</p>
                                @if ($ejercicio->musculos)
                                    <p class="mt-1">{{ implode(', ', $ejercicio->musculos) }}</p>
                                @endif
                            </div>
                        </div>
                        @if ($ejercicio->instrucciones)
                            <p class="etiqueta mt-3 text-tenue">Cómo se hace</p>
                            <ol class="mt-1.5 list-decimal space-y-1 pl-4 text-sm">
                                @foreach ($ejercicio->instrucciones as $paso)
                                    <li>{{ $paso }}</li>
                                @endforeach
                            </ol>
                        @endif
                        <a href="{{ route('ejercicios.show', $ejercicio) }}" class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-primario hover:underline">
                            Ver ficha del ejercicio <x-icono nombre="flecha-der" class="size-3.5" />
                        </a>
                    </div>
                </li>
            @empty
                <li class="py-8 text-center text-sm text-tenue">Esta rutina aún no tiene ejercicios.</li>
            @endforelse
        </ol>
    </div>

    @if ($rutina->ejercicios->isNotEmpty())
        <x-slot:pie>
            <a href="{{ route('entrenamientos.create', ['rutina' => $rutina->id]) }}"
               class="flex h-13 items-center justify-center gap-2 rounded-2xl bg-primario-fuerte font-bold text-white hover:bg-primario">
                <x-icono nombre="play" class="size-5" /> Empezar rutina
            </a>
        </x-slot:pie>
    @endif
</x-app-layout>
