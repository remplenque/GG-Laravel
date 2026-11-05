<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-xl bg-primario-fuerte px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primario focus:outline-none focus:ring-2 focus:ring-primario focus:ring-offset-2 focus:ring-offset-tarjeta disabled:opacity-40']) }}>
    {{ $slot }}
</button>
