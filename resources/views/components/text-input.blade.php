@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-borde bg-fondo px-3.5 py-2.5 text-texto placeholder:text-tenue focus:border-primario focus:ring-primario']) }}>
