<x-guest-layout>
    <h1 class="font-display text-2xl font-bold">Inicia sesión</h1>
    <p class="mt-1 text-sm text-tenue">Sigue tu racha donde la dejaste.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4" x-data="{ ver: false, enviando: false }" @submit="enviando = true">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="tu@correo.cl" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <div class="relative mt-1">
                <x-text-input id="password" class="block w-full pr-11" type="password" ::type="ver ? 'text' : 'password'" name="password" required autocomplete="current-password" />
                <button type="button" @click="ver = ! ver" :aria-label="ver ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                        class="absolute inset-y-0 right-0 grid w-11 place-items-center text-tenue transition hover:text-texto">
                    <x-icono nombre="ojo" class="size-4" x-show="! ver" />
                    <x-icono nombre="ojo-cerrado" class="size-4" x-show="ver" x-cloak />
                </button>
            </div>
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

        <x-primary-button class="h-12 w-full" ::disabled="enviando">
            <span x-show="! enviando">{{ __('Log in') }}</span>
            <span x-show="enviando" x-cloak class="size-5 rounded-full border-2 border-white/30 border-t-white animate-girar"></span>
        </x-primary-button>
    </form>

    @if (config('gogym.demo.boton') && config('gogym.demo.email'))
        <div class="my-5 flex items-center gap-3 text-[11px] uppercase tracking-widest text-tenue">
            <span class="h-px flex-1 bg-borde"></span> o <span class="h-px flex-1 bg-borde"></span>
        </div>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <input type="hidden" name="email" value="{{ config('gogym.demo.email') }}">
            <input type="hidden" name="password" value="{{ config('gogym.demo.password') }}">
            <button type="submit" class="presionable flex h-12 w-full items-center justify-center gap-2 rounded-xl border border-ia-borde bg-ia-fondo/60 text-sm font-semibold text-texto transition hover:border-ia/60">
                <x-icono nombre="cohete" class="size-4 text-ia" /> Entrar con la cuenta demo
            </button>
        </form>
    @endif

    <p class="mt-6 text-center text-sm text-tenue">
        ¿No tienes cuenta? <a href="{{ route('register') }}" class="font-semibold text-primario hover:underline">{{ __('Register') }}</a>
    </p>
</x-guest-layout>
