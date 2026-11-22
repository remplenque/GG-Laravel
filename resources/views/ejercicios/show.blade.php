{{--
    Vista 06 · Detalle de ejercicio (GET /ejercicios/{ejercicio}).

      1. Encabezado con el botón atrás.
      2. Imagen del ejercicio (posición inicial).
      3. Nombre y chips: músculos y equipo.
      4. "Cómo se hace": las instrucciones numeradas.
      5. Marcas del usuario: Última vez y Mejor marca.
      6. Consejos.
      7. Tarjeta violeta "Pregúntale al coach" con preguntas sugeridas.
      8. Agregar a una rutina.

    $ejercicio, $ultimaVez, $mejorMarca y $rutinas los manda EjercicioController@show.
    $ultimaVez y $mejorMarca son una EntrenamientoSerie o null.
--}}
@php
    // "40 kg × 10", "× 12" (sin carga) o "—" si nunca lo ha hecho.
    // Es una función anónima: se llama abajo como $marca($ultimaVez).
    $marca = function ($serie) {
        if ($serie === null) {
            return '—';
        }
        $kg = $serie->kg === null ? '' : str_replace('.', ',', (string) $serie->kg).' kg ';

        return trim($kg.'× '.($serie->reps ?? 0));
    };

    // El nombre en minúsculas va a mitad de frase: "¿Cuál es el error más común en el press de banca?".
    $nombre = Str::lower($ejercicio->nombre);
    $preguntas = [
        "¿Cuál es el error más común en {$nombre}?",
        // Sin equipo no hay nada que reemplazar: se pregunta cómo hacerlo más fácil.
        $ejercicio->equipo === 'Sin equipo'
            ? "¿Cómo hago {$nombre} más fácil si recién empiezo?"
            : "¿Con qué reemplazo {$nombre} si no tengo ".Str::lower($ejercicio->equipo).'?',
    ];
@endphp

<x-app-layout :titulo="$ejercicio->nombre">

    {{-- ===================== 1. Encabezado ===================== --}}
    {{-- url()->previous() vuelve a donde estaba (biblioteca, rutina o coach);
         si se entró directo por la URL, vuelve a la biblioteca. --}}
    <x-encabezado titulo="Detalle" etiqueta="Biblioteca de ejercicios"
                  :atras="url()->previous(route('ejercicios.index'))" class="aparecer" style="--i: 0" />

    {{-- ===================== 2. Imagen ===================== --}}
    {{-- url_imagen es un accessor del modelo (Ejercicio::urlImagen): arma la
         URL de jsDelivr con el nombre del archivo, o devuelve null. --}}
    {{-- Si hay foto del final del movimiento, las dos se alternan solas
         (animación "foto-movimiento" en app.css) para mostrar el gesto. --}}
    <div class="aparecer relative mt-4 overflow-hidden rounded-2xl border border-borde bg-white shadow-[0_20px_50px_-30px_rgb(76_141_255/0.6)]" style="--i: 1">
        @if ($ejercicio->url_imagen)
            <img src="{{ $ejercicio->url_imagen }}" alt="{{ $ejercicio->nombre }}"
                 class="aspect-video w-full bg-white object-contain" loading="lazy" decoding="async">
            @if ($ejercicio->url_imagen_final)
                <img src="{{ $ejercicio->url_imagen_final }}" alt="" aria-hidden="true"
                     class="foto-movimiento absolute inset-0 aspect-video w-full bg-white object-contain" loading="lazy" decoding="async">
                <span class="absolute bottom-2 left-2 inline-flex items-center gap-1 rounded-full bg-fondo/80 px-2 py-1 text-[10px] font-semibold text-texto backdrop-blur">
                    <x-icono nombre="play" class="size-2.5 text-primario" /> Movimiento completo
                </span>
            @endif
        @else
            <div class="grid aspect-video place-items-center bg-tarjeta text-tenue">
                <x-icono nombre="imagen" class="size-8" />
            </div>
        @endif
    </div>

    {{-- ===================== 3. Nombre y chips ===================== --}}
    <section class="aparecer mt-4" style="--i: 2">
        <h1 class="font-display text-2xl font-bold leading-tight lg:text-3xl">{{ $ejercicio->nombre }}</h1>
        <p class="mt-0.5 text-xs text-tenue">{{ $ejercicio->grupo }} · {{ $ejercicio->nivel }}</p>

        {{-- musculos es un arreglo (cast 'array' en el modelo). Los
             principales van en azul; el equipo, en gris. --}}
        <div class="mt-3 flex flex-wrap gap-1.5">
            @foreach ($ejercicio->musculos as $musculo)
                <span class="rounded-full border border-primario/60 bg-primario/12 px-2.5 py-1 text-[11px] font-medium text-primario">{{ $musculo }}</span>
            @endforeach
            <span class="rounded-full border border-borde px-2.5 py-1 text-[11px] font-medium text-tenue">{{ $ejercicio->equipo }}</span>
        </div>
    </section>

    {{-- ===================== 4. Cómo se hace ===================== --}}
    <section class="aparecer mt-5" style="--i: 3">
        <x-titulo-seccion texto="Cómo se hace" />

        {{-- instrucciones es un arreglo de textos (cast 'array'). La <ol> pone
             los números sola. El @if va afuera porque el mensaje de "vacío"
             no puede quedar dentro de la lista. --}}
        @if (count($ejercicio->instrucciones))
            <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-sm leading-relaxed">
                @foreach ($ejercicio->instrucciones as $instruccion)
                    <li>{{ $instruccion }}</li>
                @endforeach
            </ol>
        @else
            <p class="mt-2 text-sm text-tenue">Este ejercicio aún no tiene instrucciones.</p>
        @endif
    </section>

    {{-- ===================== 5. Marcas del usuario ===================== --}}
    {{-- Dos <x-stat-card> lado a lado, como en el mockup. --}}
    <div class="aparecer mt-5 grid grid-cols-2 gap-2" style="--i: 4">
        <x-stat-card :valor="$marca($ultimaVez)" etiqueta="Última vez" icono="reloj" />
        <x-stat-card :valor="$marca($mejorMarca)" etiqueta="Mejor marca" icono="trofeo" />
    </div>

    {{-- ===================== 6. Consejos ===================== --}}
    @if ($ejercicio->consejos)
        <section class="aparecer mt-5" style="--i: 5">
            <x-titulo-seccion texto="Consejos" />
            <ul class="mt-2 list-disc space-y-1.5 pl-5 text-sm leading-relaxed text-tenue">
                @foreach ($ejercicio->consejos as $consejo)
                    <li>{{ $consejo }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ===================== 7. Pregúntale al coach ===================== --}}
    {{-- Violeta = IA. Cada pregunta abre el Coach con el texto precargado:
         route('coach', ['pregunta' => …]) arma /coach?pregunta=…, y la vista
         del coach (Vicente) lo pone en el campo de texto. --}}
    <x-tarjeta variante="ia" class="aparecer mt-5 p-4" style="--i: 6">
        <p class="etiqueta flex items-center gap-1.5 text-ia">
            <x-icono nombre="chispas" class="size-4" /> Pregúntale al coach
        </p>
        <div class="mt-3 flex flex-col items-start gap-2">
            @foreach ($preguntas as $pregunta)
                <a href="{{ route('coach', ['pregunta' => $pregunta]) }}"
                   class="presionable inline-flex items-center gap-1.5 rounded-full border border-ia-borde px-3 py-1.5 text-xs font-medium text-texto transition hover:border-ia/60 hover:bg-white/5">
                    {{ $pregunta }}
                </a>
            @endforeach
        </div>
        <p class="mt-3 text-[11px] text-tenue">Orientación general. Ante dolor o lesión, consulta a un profesional.</p>
    </x-tarjeta>

    {{-- Registrar este ejercicio suelto (abre la vista 05 con él cargado). --}}
    <a href="{{ route('entrenamientos.create', ['ejercicio' => $ejercicio->id]) }}"
       class="aparecer presionable mt-3 flex items-center justify-center gap-2 rounded-2xl border border-borde bg-tarjeta/85 py-3 text-sm font-semibold transition hover:border-primario/60" style="--i: 7">
        <x-icono nombre="pesa" class="size-4 text-primario" /> Registrar este ejercicio
    </a>

    {{-- ===================== 8. Agregar a una rutina ===================== --}}
    {{-- Elige una de sus rutinas en el <select> y la manda por POST a
         ejercicios.agregar (RutinaController@agregarEjercicio), que lo suma
         al final con 3 × 10. Sin rutinas, invita a armar una con la IA. --}}
    <section class="aparecer mt-5" style="--i: 7">
        <x-titulo-seccion texto="Agregar a una rutina" />

        @if ($rutinas->isNotEmpty())
            <form method="POST" action="{{ route('ejercicios.agregar', $ejercicio) }}" class="mt-2 flex gap-2">
                @csrf
                <select name="rutina_id" aria-label="Rutina"
                        class="min-w-0 flex-1 rounded-xl border-borde bg-tarjeta text-sm text-texto focus:border-primario focus:ring-primario">
                    @foreach ($rutinas as $rutina)
                        <option value="{{ $rutina->id }}">{{ $rutina->nombre }}</option>
                    @endforeach
                </select>
                <button type="submit"
                        class="boton-primario shrink-0 rounded-xl px-4 text-sm font-semibold text-white">
                    Agregar
                </button>
            </form>
            <x-input-error :messages="$errors->get('rutina_id')" class="mt-1.5" />
        @else
            <p class="mt-2 text-sm text-tenue">
                Aún no tienes rutinas.
                <a href="{{ route('rutinas.generar') }}" class="font-semibold text-primario hover:underline">Arma una con el coach</a>.
            </p>
        @endif
    </section>
</x-app-layout>
