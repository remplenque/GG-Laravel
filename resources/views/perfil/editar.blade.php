{{--
    Editar perfil (GET /perfil/editar).

      1. Datos de entrenamiento: nombre, objetivo, nivel, peso, días y horario.
         Con ellos Inicio estima calorías y arma la meta semanal, Perfil arma
         el calendario y el coach ajusta sus consejos.
      2. Cambiar la contraseña (ruta password.update de Breeze).
      3. Borrar la cuenta y todos sus datos (pide la contraseña).

    $user y $opciones (config/gogym.php → rutina_ia) los manda PerfilController@editar.
--}}
@php
    $chip = 'chip-check flex h-11 cursor-pointer items-center justify-center rounded-xl border border-borde bg-tarjeta px-3 text-sm font-medium text-tenue'
        .' hover:text-texto peer-checked:border-primario peer-checked:bg-primario/15 peer-checked:font-semibold peer-checked:text-texto'
        .' peer-focus-visible:ring-2 peer-focus-visible:ring-primario';
    $campo = 'w-full rounded-xl border-borde bg-tarjeta text-sm text-texto placeholder:text-tenue focus:border-primario focus:ring-primario';
    $diasElegidos = array_map('intval', old('dias_entrenamiento', $user->dias_entrenamiento ?? []));
    $nombresDias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
@endphp

<x-app-layout titulo="Editar perfil" :navegacion="false">
    <x-encabezado titulo="Editar perfil" etiqueta="Perfil" :atras="route('perfil')" class="aparecer" style="--i: 0" />

    <x-aviso />

    <form id="form-perfil" method="POST" action="{{ route('perfil.actualizar') }}" class="mt-5 space-y-6">
        @csrf
        @method('PUT')

        {{-- ===================== Nombre ===================== --}}
        <div class="aparecer grid grid-cols-2 gap-3" style="--i: 1">
            <div>
                <label for="name" class="text-sm font-semibold">Nombre</label>
                <input id="name" name="name" type="text" required maxlength="60" autocomplete="given-name"
                       value="{{ old('name', $user->name) }}" class="{{ $campo }} mt-2">
                <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
            </div>
            <div>
                <label for="apellido" class="text-sm font-semibold">Apellido</label>
                <input id="apellido" name="apellido" type="text" maxlength="60" autocomplete="family-name"
                       value="{{ old('apellido', $user->apellido) }}" class="{{ $campo }} mt-2">
                <x-input-error :messages="$errors->get('apellido')" class="mt-1.5" />
            </div>
        </div>

        {{-- ===================== Objetivo ===================== --}}
        <fieldset class="aparecer" style="--i: 2">
            <legend class="text-sm font-semibold">Objetivo</legend>
            <div class="mt-2 grid grid-cols-3 gap-2">
                @foreach ($opciones['objetivos'] as $objetivo)
                    <label>
                        <input type="radio" name="objetivo" value="{{ $objetivo }}" class="peer sr-only"
                               @checked(old('objetivo', $user->objetivo) === $objetivo)>
                        <span class="{{ $chip }} text-center leading-tight">{{ $objetivo }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('objetivo')" class="mt-1.5" />
        </fieldset>

        {{-- ===================== Nivel ===================== --}}
        <fieldset class="aparecer" style="--i: 3">
            <legend class="text-sm font-semibold">Nivel</legend>
            <div class="mt-2 grid grid-cols-3 gap-2">
                @foreach ($opciones['niveles'] as $nivel)
                    <label>
                        <input type="radio" name="nivel" value="{{ $nivel }}" class="peer sr-only"
                               @checked(old('nivel', $user->nivel) === $nivel)>
                        <span class="{{ $chip }}">{{ $nivel }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('nivel')" class="mt-1.5" />
        </fieldset>

        {{-- ===================== Peso y horario ===================== --}}
        <div class="aparecer grid grid-cols-2 gap-3" style="--i: 4">
            <div>
                <label for="peso_kg" class="text-sm font-semibold">Peso <span class="font-normal text-tenue">(kg)</span></label>
                <input id="peso_kg" name="peso_kg" type="number" inputmode="decimal" step="0.1" min="30" max="300"
                       value="{{ old('peso_kg', $user->peso_kg) }}" placeholder="Ej.: 72,5" class="{{ $campo }} mt-2">
                <p class="mt-1 text-[11px] text-tenue">Para estimar las calorías.</p>
                <x-input-error :messages="$errors->get('peso_kg')" class="mt-1.5" />
            </div>
            <div>
                <label for="hora_entrenamiento" class="text-sm font-semibold">Horario <span class="font-normal text-tenue">(opcional)</span></label>
                <input id="hora_entrenamiento" name="hora_entrenamiento" type="time"
                       value="{{ old('hora_entrenamiento', $user->hora_entrenamiento ? substr($user->hora_entrenamiento, 0, 5) : '') }}"
                       class="{{ $campo }} mt-2">
                <x-input-error :messages="$errors->get('hora_entrenamiento')" class="mt-1.5" />
            </div>
        </div>

        {{-- ===================== Días ===================== --}}
        <fieldset class="aparecer" style="--i: 5">
            <legend class="text-sm font-semibold">Días de entrenamiento</legend>
            <p class="mt-0.5 text-xs text-tenue">Arman tu calendario y tu meta semanal.</p>
            <div class="mt-2 grid grid-cols-7 gap-1.5">
                @foreach ($nombresDias as $indice => $nombreDia)
                    <label>
                        <input type="checkbox" name="dias_entrenamiento[]" value="{{ $indice }}" class="peer sr-only"
                               @checked(in_array($indice, $diasElegidos, true))>
                        <span class="{{ $chip }} px-0 text-xs">{{ $nombreDia }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="collect($errors->get('dias_entrenamiento*'))->flatten()->all()" class="mt-1.5" />
        </fieldset>
    </form>

    {{-- ===================== Contraseña ===================== --}}
    <section class="aparecer mt-10" style="--i: 6">
        <x-titulo-seccion texto="Contraseña" />
        @if (session('status') === 'password-updated')
            <p class="mt-2 flex items-center gap-1.5 text-sm text-exito" role="status">
                <x-icono nombre="check" class="size-4" /> Contraseña actualizada.
            </p>
        @endif
        <form method="POST" action="{{ route('password.update') }}" class="mt-2 space-y-3" x-data="{ ver: false }">
            @csrf
            @method('PUT')
            <div>
                <label for="current_password" class="sr-only">Contraseña actual</label>
                <input id="current_password" name="current_password" :type="ver ? 'text' : 'password'" type="password" autocomplete="current-password"
                       placeholder="Contraseña actual" class="{{ $campo }}">
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1.5" />
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label for="password" class="sr-only">Nueva contraseña</label>
                    <input id="password" name="password" :type="ver ? 'text' : 'password'" type="password" autocomplete="new-password"
                           placeholder="Nueva" class="{{ $campo }}">
                </div>
                <div>
                    <label for="password_confirmation" class="sr-only">Repite la nueva contraseña</label>
                    <input id="password_confirmation" name="password_confirmation" :type="ver ? 'text' : 'password'" type="password" autocomplete="new-password"
                           placeholder="Repítela" class="{{ $campo }}">
                </div>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1.5" />
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-xs text-tenue">
                    <input type="checkbox" x-model="ver" class="rounded border-borde bg-tarjeta text-primario focus:ring-primario"> Mostrar
                </label>
                <button type="submit" class="presionable rounded-xl border border-borde bg-tarjeta px-4 py-2 text-sm font-semibold transition hover:border-primario/60">
                    Cambiar contraseña
                </button>
            </div>
        </form>
    </section>

    {{-- ===================== Borrar cuenta ===================== --}}
    <section class="aparecer mt-10" style="--i: 7" x-data="{ abierto: @js($errors->eliminarCuenta->isNotEmpty()) }">
        <x-titulo-seccion texto="Privacidad" />
        <div class="mt-2 rounded-2xl border border-error/25 bg-error/[0.04] p-4">
            <p class="text-sm font-semibold">Borrar mi cuenta</p>
            <p class="mt-1 text-xs leading-relaxed text-tenue">
                Elimina tu cuenta, tus rutinas, tu historial y tus conversaciones con el coach. No se puede deshacer.
            </p>
            <button type="button" x-show="! abierto" @click="abierto = true; $nextTick(() => $refs.clave.focus())"
                    class="mt-3 text-sm font-semibold text-error hover:underline">
                Quiero borrar mi cuenta
            </button>
            <form method="POST" action="{{ route('perfil.eliminar') }}" x-show="abierto" x-cloak x-transition.opacity.duration.200ms class="mt-3 space-y-2">
                @csrf
                @method('DELETE')
                <label for="clave-eliminar" class="text-xs text-tenue">Escribe tu contraseña para confirmar</label>
                <input id="clave-eliminar" x-ref="clave" name="password" type="password" autocomplete="current-password" required
                       class="{{ $campo }} focus:border-error focus:ring-error">
                <x-input-error :messages="$errors->eliminarCuenta->get('password')" />
                <div class="flex gap-2">
                    <button type="button" @click="abierto = false" class="flex-1 rounded-xl border border-borde px-4 py-2.5 text-sm font-semibold">Cancelar</button>
                    <button type="submit" class="flex-1 rounded-xl bg-error px-4 py-2.5 text-sm font-bold text-[#2a1418] transition hover:brightness-110">Borrar todo</button>
                </div>
            </form>
        </div>
    </section>

    <x-slot:pie>
        <button type="submit" form="form-perfil"
                class="boton-primario flex h-12 w-full items-center justify-center gap-2 rounded-2xl text-sm font-bold text-white">
            <x-icono nombre="check" class="size-4" /> Guardar cambios
        </button>
    </x-slot:pie>
</x-app-layout>
