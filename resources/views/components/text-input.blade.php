@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'h-12 rounded-xl border-borde bg-fondo/70 px-3.5 text-texto placeholder:text-tenue/70 transition focus:border-primario focus:ring-primario']) }}>
