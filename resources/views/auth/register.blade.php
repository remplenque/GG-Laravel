<x-guest-layout>
    <h1 class="font-display text-xl font-bold">Crea tu cuenta</h1>
    <p class="mt-1 text-sm text-tenue">Registra tus entrenamientos y arma rutinas con el coach.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-5 space-y-4">
        @csrf

        <div class="grid grid-cols-2 gap-3">
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="given-name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="apellido" :value="__('Last name')" />
                <x-text-input id="apellido" class="mt-1 block w-full" type="text" name="apellido" :value="old('apellido')" autocomplete="family-name" />
                <x-input-error :messages="$errors->get('apellido')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">{{ __('Register') }}</x-primary-button>
    </form>

    <p class="mt-5 text-center text-sm text-tenue">
        {{ __('Already registered?') }} <a href="{{ route('login') }}" class="font-semibold text-primario hover:underline">{{ __('Log in') }}</a>
    </p>
</x-guest-layout>
