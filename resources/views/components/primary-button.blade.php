<button {{ $attributes->merge(['type' => 'submit', 'class' => 'boton-primario inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primario focus-visible:ring-offset-2 focus-visible:ring-offset-tarjeta']) }}>
    {{ $slot }}
</button>
