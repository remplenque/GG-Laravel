<x-guest-layout>
    <h1 class="font-display text-xl font-bold">Inicia sesión</h1>
    <p class="mt-1 text-sm text-tenue">Sigue tu racha donde la dejaste.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-tenue">
                <input id="remember_me" type="checkbox" class="rounded border-borde bg-fondo text-primario-fuerte focus:ring-primario" name="remember">
                {{ __('Remember me') }}
            </label>
            @if (Route::has('password.request'))
                <a class="text-sm text-primario hover:underline" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
            @endif
        </div>

        <x-primary-button class="w-full">{{ __('Log in') }}</x-primary-button>
    </form>

    <p class="mt-5 text-center text-sm text-tenue">
        ¿No tienes cuenta? <a href="{{ route('register') }}" class="font-semibold text-primario hover:underline">{{ __('Register') }}</a>
    </p>
</x-guest-layout>
