@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-tenue']) }}>
    {{ $value ?? $slot }}
</label>
