{{--
    Componente <x-aviso>: muestra el mensaje que deja un controlador en la
    sesión al redirigir, con ->with('estado', '…') o ->with('error', '…').
    Esos mensajes duran una sola petición ("flash"): al recargar, desaparecen.

    Uso: <x-aviso class="mt-4" />  (va en cualquier vista; si no hay mensaje, no dibuja nada)

      - estado: confirmación en azul ("Guardamos 4 rutinas…").
      - error:  problema en rojo ("El coach no pudo armar…").
--}}
@if (session('error'))
    <p {{ $attributes->merge(['class' => 'rounded-xl border border-error/50 bg-error/10 px-3 py-2 text-sm text-error']) }} role="alert">
        {{ session('error') }}
    </p>
@elseif (session('estado'))
    <p {{ $attributes->merge(['class' => 'rounded-xl border border-primario/50 bg-primario/10 px-3 py-2 text-sm']) }} role="status">
        {{ session('estado') }}
    </p>
@endif
