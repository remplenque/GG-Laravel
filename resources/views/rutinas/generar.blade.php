{{--
    Vista 03 · Generar rutina con IA (GET /rutinas/generar).

    Formulario "Arma tu rutina": objetivo, nivel, días por semana, minutos por
    sesión, equipamiento y molestias. Se envía por POST a rutinas.proponer.

    Chips sin JavaScript: cada chip es un <label> que envuelve un <input>
    escondido (sr-only, sigue siendo accesible para lectores de pantalla) con
    la clase "peer". El <span> de al lado usa peer-checked:… de Tailwind, que
    aplica esos estilos solo cuando el input está marcado. Los radio permiten
    uno por grupo (mismo name); los checkbox, varios (name="equipamiento[]").

    old('campo', $porDefecto): si la validación falló, devuelve lo que el
    usuario había elegido; si no, el valor por defecto (su perfil).

    $opciones (config/gogym.php → rutina_ia) y $user los manda RutinaController@generar.
--}}
@php
    // Clases de cada chip; las de peer-checked: lo pintan de azul al marcarlo.
    $chip = 'flex h-11 cursor-pointer items-center justify-center rounded-xl border border-borde bg-tarjeta px-3 text-sm font-medium text-tenue transition'
        .' hover:text-texto peer-checked:border-primario peer-checked:bg-primario/15 peer-checked:font-semibold peer-checked:text-texto'
        .' peer-focus-visible:ring-2 peer-focus-visible:ring-primario';

    // Días por defecto: los que tiene marcados en su perfil, si están entre 2 y 6.
    $diasPerfil = count($user->dias_entrenamiento ?? []);
    $diasPorDefecto = in_array($diasPerfil, $opciones['dias'], true) ? $diasPerfil : 3;
@endphp

<x-app-layout titulo="Arma tu rutina" :navegacion="false">
    <x-encabezado titulo="Arma tu rutina" etiqueta="Coach IA" :ia="true" :atras="route('rutinas.index')"
                  class="aparecer" style="--i: 0" />

    {{-- Mensaje que deja el controlador con ->with('estado', …). --}}
    @if (session('estado'))
        <p class="mt-4 rounded-xl border border-ia-borde bg-ia-fondo px-3 py-2 text-sm">{{ session('estado') }}</p>
    @endif

    {{-- id="form-rutina": el botón de abajo vive fuera del <form> (en el pie
         fijo del layout) y se conecta con el atributo form="form-rutina". --}}
    <form id="form-rutina" method="POST" action="{{ route('rutinas.proponer') }}" class="mt-5 space-y-5">
        {{-- @csrf: token que prueba que el formulario salió de esta app. Sin
             él, Laravel rechaza cualquier POST (error 419). --}}
        @csrf

        {{-- ===================== Objetivo (uno) ===================== --}}
        <fieldset class="aparecer" style="--i: 1">
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

        {{-- ===================== Nivel (uno) ===================== --}}
        <fieldset class="aparecer" style="--i: 2">
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

        {{-- ===================== Días por semana (uno) ===================== --}}
        {{-- old() devuelve texto ("4"); (int) lo pasa a número para comparar. --}}
        <fieldset class="aparecer" style="--i: 3">
            <legend class="text-sm font-semibold">Días por semana</legend>
            <div class="mt-2 grid grid-cols-5 gap-2">
                @foreach ($opciones['dias'] as $dias)
                    <label>
                        <input type="radio" name="dias" value="{{ $dias }}" class="peer sr-only"
                               @checked((int) old('dias', $diasPorDefecto) === $dias)>
                        <span class="{{ $chip }}">{{ $dias }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('dias')" class="mt-1.5" />
        </fieldset>

        {{-- ===================== Minutos por sesión (uno) ===================== --}}
        <fieldset class="aparecer" style="--i: 4">
            <legend class="text-sm font-semibold">Minutos por sesión</legend>
            <div class="mt-2 grid grid-cols-3 gap-2">
                @foreach ($opciones['minutos'] as $minutos)
                    <label>
                        <input type="radio" name="minutos" value="{{ $minutos }}" class="peer sr-only"
                               @checked((int) old('minutos', 60) === $minutos)>
                        <span class="{{ $chip }}">{{ $minutos }} min</span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('minutos')" class="mt-1.5" />
        </fieldset>

        {{-- ===================== Equipamiento (varios) ===================== --}}
        <fieldset class="aparecer" style="--i: 5">
            <legend class="text-sm font-semibold">Equipamiento disponible</legend>

            {{-- Las llaves del mapa son los nombres de los chips. Son checkbox:
                 name="equipamiento[]" hace que llegue un arreglo, y old()
                 también devuelve un arreglo, por eso se marca con in_array. --}}
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach (array_keys($opciones['equipamiento']) as $nombre)
                    <label>
                        <input type="checkbox" name="equipamiento[]" value="{{ $nombre }}" class="peer sr-only"
                               @checked(in_array($nombre, old('equipamiento', ['Barra', 'Mancuernas', 'Máquinas']), true))>
                        <span class="{{ $chip }} px-4">{{ $nombre }}</span>
                    </label>
                @endforeach
            </div>

            {{-- Los errores pueden ser del arreglo ('equipamiento') o de un
                 elemento ('equipamiento.1'). get('equipamiento*') junta ambos,
                 pero agrupados por campo; flatten() los deja en una sola lista,
                 que es lo que espera <x-input-error>. --}}
            <x-input-error :messages="collect($errors->get('equipamiento*'))->flatten()->all()" class="mt-1.5" />
        </fieldset>

        {{-- ===================== Molestias (opcional) ===================== --}}
        <div class="aparecer" style="--i: 6">
            <label for="molestias" class="text-sm font-semibold">
                Molestias o preferencias <span class="font-normal text-tenue">(opcional)</span>
            </label>
            <textarea id="molestias" name="molestias" rows="3" maxlength="{{ $opciones['max_molestias'] }}"
                      placeholder="Ej.: molestia leve en el hombro derecho"
                      class="mt-2 w-full rounded-xl border-borde bg-tarjeta text-sm text-texto placeholder:text-tenue focus:border-primario focus:ring-primario">{{ old('molestias') }}</textarea>
            <x-input-error :messages="$errors->get('molestias')" class="mt-1.5" />
        </div>
    </form>

    {{-- ===================== Botón fijo abajo ===================== --}}
    {{-- form="form-rutina" lo conecta con el formulario de arriba. El evento
         submit sube desde el <form> hasta la ventana, y @submit.window lo
         escucha aquí: marca "enviando", que desactiva el botón (para no
         gastar dos llamadas a la IA) y cambia el texto mientras responde. --}}
    <x-slot:pie>
        <div x-data="{ enviando: false }" @submit.window="enviando = true">
            <button type="submit" form="form-rutina" x-bind:disabled="enviando"
                    class="flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-primario-fuerte text-sm font-bold text-white hover:bg-primario disabled:opacity-60">
                <x-icono nombre="chispas" class="size-4" />
                <span x-text="enviando ? 'Generando…' : 'Generar rutina'">Generar rutina</span>
            </button>
            <p class="mt-2 text-center text-[11px] text-tenue">
                La IA propone y tú decides qué guardar. Puedes editarla después.
            </p>
        </div>
    </x-slot:pie>
</x-app-layout>
