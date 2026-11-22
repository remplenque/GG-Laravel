<x-guest-layout>
    <h1 class="font-display text-2xl font-bold">Recupera tu cuenta</h1>
    <div class="mb-4 mt-1 text-sm text-tenue">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-5">
            <x-primary-button class="h-12 w-full">
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>

    <p class="mt-6 text-center text-sm text-tenue">
        <a href="{{ route('login') }}" class="font-semibold text-primario hover:underline">Volver a iniciar sesión</a>
    </p>
</x-guest-layout>
