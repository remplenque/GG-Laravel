@use('App\Support\Formato')

<x-app-layout titulo="Entrenamientos">
    <x-encabezado titulo="Entrenamientos" subtitulo="Tu historial de sesiones" :atras="route('inicio')">
        <a href="{{ route('entrenamientos.create') }}"
           class="grid size-10 place-items-center rounded-xl bg-primario-fuerte text-white hover:bg-primario"
           aria-label="Registrar entrenamiento">
            <x-icono nombre="mas" />
        </a>
    </x-encabezado>

    <x-aviso class="mt-4" />

    <div class="mt-5 space-y-3">
        @forelse ($entrenamientos as $entrenamiento)
            <x-tarjeta class="p-0">
                <div class="flex items-start gap-3 p-4">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primario/15 font-bold text-primario">
                        {{ mb_strtoupper(mb_substr($entrenamiento->nombre, 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">{{ $entrenamiento->titulo }}</p>
                        <p class="mt-0.5 text-xs text-tenue">
                            {{ Formato::fechaLarga($entrenamiento->inicio) }} · {{ Formato::minutos($entrenamiento->duracion_minutos) }}
                        </p>
                        <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-tenue">
                            <span>{{ $entrenamiento->series->groupBy('ejercicio_id')->count() }} ejercicios</span>
                            <span>{{ $entrenamiento->series->count() }} series</span>
                            <span>{{ Formato::miles($entrenamiento->volumen) }} kg</span>
                        </div>
                    </div>
                </div>
                <div class="flex border-t border-borde">
                    <a href="{{ route('entrenamientos.edit', $entrenamiento) }}"
                       class="flex-1 px-4 py-2.5 text-center text-sm font-semibold text-primario hover:bg-white/5">
                        Editar
                    </a>
                    <form method="POST" action="{{ route('entrenamientos.destroy', $entrenamiento) }}"
                          class="flex-1 border-l border-borde" onsubmit="return confirm('¿Eliminar este entrenamiento del historial?')">
                        @csrf @method('DELETE')
                        <button class="w-full px-4 py-2.5 text-sm font-semibold text-error hover:bg-white/5">Eliminar</button>
                    </form>
                </div>
            </x-tarjeta>
        @empty
            <x-tarjeta class="border-dashed py-10 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-xl bg-primario/15 text-primario">
                    <x-icono nombre="pesa" />
                </span>
                <p class="mt-3 font-semibold">Aún no registras entrenamientos</p>
                <p class="mt-1 text-sm text-tenue">Guarda tus series para ver tu progreso y recibir mejores sugerencias.</p>
                <a href="{{ route('entrenamientos.create') }}"
                   class="mt-4 inline-flex rounded-xl bg-primario-fuerte px-4 py-2.5 text-sm font-semibold text-white">
                    Registrar el primero
                </a>
            </x-tarjeta>
        @endforelse
    </div>

    @if ($entrenamientos->hasPages())
        <div class="mt-5">{{ $entrenamientos->links() }}</div>
    @endif
</x-app-layout>
