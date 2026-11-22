<x-guest-layout>
    <h1 class="font-display text-2xl font-bold">Crea tu cuenta</h1>
    <p class="mt-1 text-sm text-tenue">Registra tus entrenamientos y arma rutinas con el coach.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4" x-data="{ ver: false, enviando: false }" @submit="enviando = true">
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
            <div class="relative mt-1">
                <x-text-input id="password" class="block w-full pr-11" type="password" ::type="ver ? 'text' : 'password'" name="password" required autocomplete="new-password" />
                <button type="button" @click="ver = ! ver" :aria-label="ver ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                        class="absolute inset-y-0 right-0 grid w-11 place-items-center text-tenue transition hover:text-texto">
                    <x-icono nombre="ojo" class="size-4" x-show="! ver" />
                    <x-icono nombre="ojo-cerrado" class="size-4" x-show="ver" x-cloak />
                </button>
            </div>
            <p class="mt-1 text-[11px] text-tenue">Mínimo 8 caracteres.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" ::type="ver ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="h-12 w-full" ::disabled="enviando">
            <span x-show="! enviando">{{ __('Register') }}</span>
            <span x-show="enviando" x-cloak class="size-5 rounded-full border-2 border-white/30 border-t-white animate-girar"></span>
        </x-primary-button>

        <p class="text-center text-[11px] leading-relaxed text-tenue">
            Tus preguntas al coach se envían a un servicio de IA sin tu nombre ni tu correo. Puedes borrar tu cuenta cuando quieras.
        </p>
    </form>

    <p class="mt-6 text-center text-sm text-tenue">
        {{ __('Already registered?') }} <a href="{{ route('login') }}" class="font-semibold text-primario hover:underline">{{ __('Log in') }}</a>
    </p>
</x-guest-layout>
