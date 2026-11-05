{{-- Rutinas de la cuenta. La que toca según la rotación va destacada y cada
     una muestra su lugar en la rotación. --}}
<x-app-layout titulo="Rutinas">
    <header class="flex items-end gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-xs text-tenue">
                @if ($rutinas->isEmpty())
                    Tu plan de entrenamiento
                @else
                    {{ $rutinas->count() }} {{ $rutinas->count() === 1 ? 'rutina' : 'rutinas' }} · rotan en orden
                @endif
            </p>
            <h1 class="font-display text-2xl font-bold">Rutinas</h1>
        </div>
        <a href="{{ route('rutinas.generar') }}"
           class="inline-flex items-center gap-1.5 rounded-xl border border-ia-borde bg-ia-fondo px-3 py-2 text-xs font-semibold text-ia hover:bg-ia/10">
            <x-icono nombre="chispas" class="size-4" /> Generar con IA
        </a>
    </header>

    <a href="{{ route('ejercicios.index') }}" class="mt-3 flex items-center gap-2 text-sm text-tenue hover:text-texto">
        <x-icono nombre="libro" class="size-4" /> Biblioteca de ejercicios
        <x-icono nombre="flecha-der" class="size-4" />
    </a>

    @forelse ($rutinas as $rutina)
        @php($esSiguiente = $rutina->id === $siguiente?->id)
        <x-tarjeta :href="route('rutinas.show', $rutina)" :variante="$esSiguiente ? 'destacada' : 'normal'" class="mt-3 flex items-center gap-3.5 p-3">
            <div class="relative shrink-0">
                <x-foto-rutina :categoria="$rutina->categoria" class="size-16 rounded-xl" />
                @isset($orden[$rutina->id])
                    <span @class([
                        'absolute -left-1.5 -top-1.5 grid size-[22px] place-items-center rounded-full border text-[11px] font-bold',
                        'border-primario bg-primario text-white' => $esSiguiente,
                        'border-borde bg-tarjeta text-tenue' => ! $esSiguiente,
                    ])>
                        {{ $orden[$rutina->id] }}
                        <span class="sr-only">en la rotación</span>
                    </span>
                @endisset
            </div>

            <div class="min-w-0 flex-1">
                @php($etiqueta = collect([$rutina->categoria ?: null, $esSiguiente ? 'Siguiente' : null])->filter()->join(' · '))
                @if ($etiqueta !== '')
                    <p class="etiqueta text-primario">{{ $etiqueta }}</p>
                @endif
                <p class="mt-0.5 truncate font-bold">{{ $rutina->nombre }}</p>
                <p class="mt-0.5 text-xs text-tenue">{{ $rutina->resumen }}</p>
                @if ($rutina->grupos)
                    <ul class="mt-2 flex flex-wrap gap-1.5">
                        @foreach (array_slice($rutina->grupos, 0, 3) as $grupo)
                            <li class="rounded-full border border-borde px-2 py-0.5 text-[11px] text-tenue">{{ $grupo }}</li>
                        @endforeach
                        @if (count($rutina->grupos) > 3)
                            <li class="rounded-full border border-borde px-2 py-0.5 text-[11px] text-tenue">+{{ count($rutina->grupos) - 3 }}</li>
                        @endif
                    </ul>
                @endif
            </div>
            <x-icono nombre="flecha-der" class="text-tenue" />
        </x-tarjeta>
    @empty
        <div class="mt-16 flex flex-col items-center px-4 text-center">
            <span class="grid size-[72px] place-items-center rounded-full bg-primario/12 text-primario">
                <x-icono nombre="lista" class="size-8" />
            </span>
            <p class="mt-4 font-bold">Aún no tienes rutinas</p>
            <p class="mt-1.5 text-sm text-tenue">Cuando armes una, aparecerá aquí con sus ejercicios.</p>
            <a href="{{ route('rutinas.generar') }}" class="mt-5 inline-flex items-center gap-1.5 rounded-xl bg-primario-fuerte px-4 py-2.5 text-sm font-semibold text-white">
                <x-icono nombre="chispas" class="size-4" /> Arma tu rutina con IA
            </a>
        </div>
    @endforelse
</x-app-layout>
