{{--
    Componente <x-aviso>: muestra el mensaje que deja un controlador en la
    sesión al redirigir, con ->with('estado', '…') o ->with('error', '…').
    Esos mensajes duran una sola petición ("flash"): al recargar, desaparecen.

    Se dibuja como un aviso flotante (toast) arriba de la pantalla, sin
    importar dónde se ponga <x-aviso /> en la vista:
      - estado: confirmación en azul, se cierra sola a los 4,5 s.
      - error:  problema en rojo, queda hasta que se cierra.

    Si además viene session('logros') (récords del entrenamiento recién
    guardado), lanza confeti.
--}}
@php
    $error = session('error');
    $estado = session('estado');
@endphp

@if ($error || $estado)
    <div class="pointer-events-none fixed inset-x-0 top-0 z-[80] flex justify-center px-4 pt-[max(1rem,env(safe-area-inset-top))] lg:left-64">
        <div x-data="toast({{ $error ? 0 : 4500 }})" x-show="visible"
             x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-3"
             @if (session('celebrar')) x-init="setTimeout(() => window.confeti?.(), 250)" @endif
             @class([
                 'toast pointer-events-auto relative w-full max-w-md overflow-hidden rounded-2xl border shadow-2xl shadow-black/40 backdrop-blur-xl',
                 'border-error/40 bg-[#2a1418]/90' => $error,
                 'border-primario/40 bg-[#101c36]/90' => ! $error,
             ])
             role="{{ $error ? 'alert' : 'status' }}">
            <div class="flex items-start gap-3 px-4 py-3">
                <span @class([
                    'mt-0.5 grid size-6 shrink-0 place-items-center rounded-full',
                    'bg-error/20 text-error' => $error,
                    'bg-exito/15 text-exito' => ! $error,
                ])>
                    <x-icono :nombre="$error ? 'alerta' : 'check'" class="size-3.5" />
                </span>
                <p class="flex-1 text-sm leading-relaxed {{ $error ? 'text-red-100' : 'text-texto' }}">{{ $error ?: $estado }}</p>
                <button type="button" @click="visible = false" class="-mr-1 grid size-7 shrink-0 place-items-center rounded-lg text-tenue transition hover:bg-white/10 hover:text-texto" aria-label="Cerrar aviso">
                    <x-icono nombre="cerrar" class="size-4" />
                </button>
            </div>
            @unless ($error)
                <span class="toast-tiempo absolute inset-x-0 bottom-0 h-0.5 bg-gradient-to-r from-primario to-ia" style="--duracion: 4500ms"></span>
            @endunless
        </div>
    </div>
@endif
