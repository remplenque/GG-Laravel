{{--
    Pantalla de espera mientras la IA trabaja (generar o regenerar una rutina
    tarda unos segundos). Se activa sola al enviar cualquier formulario con el
    atributo data-ia y muestra mensajes que van rotando.

    Uso: <x-cargando-ia titulo="Armando tu rutina" :pasos="['…', '…']" />
    Va fuera de cualquier contenedor con blur o transform (si no, "fixed" se
    quedaría encerrado en él).
--}}
@props(['titulo' => 'El coach está trabajando', 'pasos' => []])

<div x-data="{ activo: false, paso: 0, total: {{ count($pasos) }}, reloj: null }"
     @submit.window="if ($event.target.dataset.ia !== undefined && ! $event.defaultPrevented) {
         activo = true;
         paso = 0;
         reloj = setInterval(() => paso = Math.min(paso + 1, total - 1), 1600);
     }"
     @pageshow.window="activo = false; clearInterval(reloj)"
     x-show="activo" x-cloak
     x-transition:enter="transition duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     class="fixed inset-0 z-[70] grid place-items-center bg-fondo/80 px-6 backdrop-blur-md"
     role="status" aria-live="polite">
    <div class="w-full max-w-xs text-center">
        <div class="relative mx-auto size-24">
            <span class="absolute inset-0 rounded-full bg-ia/20 blur-2xl"></span>
            <span class="absolute inset-0 rounded-full border-2 border-ia/20 border-t-ia animate-girar"></span>
            <span class="orbe-ia absolute inset-3 grid place-items-center rounded-full text-white">
                <x-icono nombre="chispas" class="size-8" />
            </span>
        </div>
        <p class="mt-6 font-display text-lg font-bold">{{ $titulo }}</p>
        <div class="relative mt-2 h-10">
            @foreach ($pasos as $i => $texto)
                <p x-show="paso === {{ $i }}"
                   x-transition:enter="transition duration-300 delay-150" x-transition:enter-start="opacity-0 translate-y-2"
                   x-transition:leave="transition duration-150 absolute inset-x-0" x-transition:leave-end="opacity-0 -translate-y-2"
                   class="text-sm text-tenue">{{ $texto }}</p>
            @endforeach
        </div>
        <div class="mx-auto mt-4 flex justify-center gap-1.5">
            @foreach ($pasos as $i => $texto)
                <span class="h-1 rounded-full transition-all duration-500" :class="paso >= {{ $i }} ? 'w-6 bg-ia' : 'w-2 bg-borde'"></span>
            @endforeach
        </div>
    </div>
</div>
