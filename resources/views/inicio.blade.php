{{--
    Vista 01 · Inicio (dashboard), según el mockup de la Unidad 3.

    De arriba hacia abajo: saludo con el menú de cuenta, la racha de la semana,
    tres números de la semana, la tarjeta violeta del Coach IA con la
    sugerencia de hoy y las últimas sesiones.

    Las variables ($user, $hoy, $racha, $estaSemana, $siguiente, $sugerencia y
    $ultimas) las manda InicioController: la vista solo las pinta, no calcula.

    Recuerda: los comentarios de Blade (los que abren con llave, llave, guion,
    guion) NO llegan al navegador; los comentarios HTML sí se pueden ver.

    Animaciones de entrada (definidas en resources/css/app.css y
    resources/js/app.js): cada bloque lleva la clase "aparecer" y un
    style="--i: N"; N es su turno en la cascada (N × 45 ms de espera), así la
    pantalla se arma de arriba hacia abajo. Los cuadritos de la racha entran
    con "crecer" y los números cuentan desde 0 con x-contar. Son animaciones
    cortas y suaves a propósito, para no recargar la pantalla. Con "reducir
    movimiento" activado en el sistema, todo aparece directo.
--}}

{{-- @use importa la clase Formato para escribir Formato::miles(...) en vez
     del nombre completo App\Support\Formato. --}}
@use('App\Support\Formato')

{{-- <x-app-layout> es el componente-layout de las pantallas con sesión
     (resources/views/components/app-layout.blade.php): pone el <head>, el
     @vite con Tailwind y la barra de navegación inferior. Todo lo que va
     entre sus etiquetas aterriza en su {{ $slot }}. --}}
<x-app-layout titulo="Inicio">

    {{-- ===================== 1. Saludo y menú de cuenta ===================== --}}
    {{-- Primero en la cascada (--i: 0, sin espera). Lleva z-index (relative
         z-20) porque la animación crea una capa nueva y, sin él, el menú de
         cuenta quedaría tapado por las tarjetas de abajo. --}}
    <header class="aparecer relative z-20 flex items-center gap-3" style="--i: 0">
        <div class="min-w-0 flex-1">
            {{-- "Lunes 9 de noviembre" en español (usa APP_LOCALE=es). --}}
            <p class="text-xs text-tenue">{{ Formato::fechaLarga($hoy) }}</p>
            {{-- {{ }} imprime el valor escapado: si alguien se llama "<b>",
                 se muestra como texto y no como HTML. --}}
            <h1 class="truncate font-display text-2xl font-bold">
                {{ $user->name ? 'Hola, '.$user->name : 'Hola' }}
            </h1>
        </div>

        {{-- Avatar con las iniciales que abre un menú (Ver perfil / Cerrar
             sesión). x-data, @click y x-show son de Alpine.js (cargado en
             resources/js/app.js): "abierto" es una variable del navegador que
             muestra u oculta el menú sin recargar la página. --}}
        <div class="relative" x-data="{ abierto: false }" @keydown.escape.window="abierto = false">
            <button type="button" @click="abierto = !abierto" :aria-expanded="abierto"
                    class="grid size-10 place-items-center rounded-full border-[1.5px] border-primario bg-primario/15 text-sm font-bold text-primario"
                    aria-label="Abrir menú de cuenta">
                @if ($user->iniciales !== '')
                    {{ $user->iniciales }}
                @else
                    <x-icono nombre="usuario" />
                @endif
            </button>
            {{-- x-cloak lo deja oculto hasta que Alpine arranca (evita un
                 parpadeo); @click.outside lo cierra al tocar fuera. --}}
            <div x-cloak x-show="abierto" x-transition.origin.top.right @click.outside="abierto = false"
                 class="absolute right-0 z-30 mt-2 w-48 overflow-hidden rounded-xl border border-borde bg-tarjeta-alta py-1 shadow-xl">
                <a href="{{ route('perfil') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold hover:bg-white/5">
                    <x-icono nombre="usuario" /> Ver perfil
                </a>
                {{-- Cerrar sesión cambia algo en el servidor, así que va como
                     POST y no como enlace. @csrf agrega el campo escondido con
                     el código de la sesión; sin él Laravel responde 419. --}}
                <form method="POST" action="{{ route('logout') }}" class="border-t border-borde">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-sm font-semibold text-error hover:bg-white/5">
                        <x-icono nombre="salir" /> Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- ===================== 2. Racha de la semana ===================== --}}
    {{-- <x-tarjeta> es el contenedor redondeado del mockup (componente en
         resources/views/components/tarjeta.blade.php). --}}
    <x-tarjeta class="aparecer mt-4" style="--i: 1">
        <div class="flex items-center gap-1.5">
            {{-- :class con dos puntos = el valor es PHP, no texto fijo. La
                 llama va azul si hay racha y gris si está en 0. --}}
            <x-icono nombre="llama" :class="$racha['actual'] > 0 ? 'text-primario' : 'text-tenue'" />
            {{-- Los días cuentan desde 0 (x-contar), empezando a los 250 ms,
                 cuando la tarjeta ya llegó a su lugar. Solo cambia el número;
                 "días seguidos" se queda fijo. --}}
            <p class="flex-1 text-sm font-bold tabular-nums" x-data x-contar.250="{{ $racha['actual'] }}">
                {{ $racha['actual'] }} {{ $racha['actual'] === 1 ? 'día seguido' : 'días seguidos' }}
            </p>
            <p class="text-xs text-tenue">Récord {{ $racha['record'] }}</p>
        </div>

        {{-- Un cuadrito por día, de lunes a domingo. @foreach recorre la
             lista que armó Historial::semana() y $dia es cada elemento. --}}
        <ol class="mt-3 grid grid-cols-7 gap-1.5">
            @foreach ($racha['dias'] as $dia)
                <li class="text-center" title="{{ $dia['nombre'] }}">
                    {{-- Las letras van arriba como en el mockup (L M M J V S D).
                         @class aplica cada clase solo si su condición es
                         verdadera: hoy va en blanco y negrita. --}}
                    <span @class([
                        'mb-1 block text-[11px]',
                        'font-bold text-texto' => $dia['estado'] === 'hoy',
                        'text-tenue' => $dia['estado'] !== 'hoy',
                    ])>{{ $dia['inicial'] }}</span>
                    {{-- El cuadrito: lleno si entrenó, punteado si es hoy y aún
                         no entrena, apagado si descansó y con borde si viene.
                         Entra con "crecer", uno tras otro: $loop->index es la
                         vuelta del @foreach (0 = lunes), y cada uno espera
                         35 ms más que el anterior. --}}
                    <span style="--retraso: {{ 150 + $loop->index * 35 }}ms" @class([
                        'crecer block h-7 rounded-[7px]',
                        'bg-primario-fuerte' => $dia['estado'] === 'entrenado',
                        'border-[1.5px] border-dashed border-texto/70' => $dia['estado'] === 'hoy',
                        'bg-primario/12' => $dia['estado'] === 'descanso',
                        'border border-borde bg-tarjeta-alta' => $dia['estado'] === 'futuro',
                    ])></span>
                    {{-- Texto solo para lectores de pantalla (sr-only lo esconde
                         a la vista, pero se lee en voz alta). --}}
                    <span class="sr-only">{{ $dia['nombre'] }}: {{ $dia['estado'] }}</span>
                </li>
            @endforeach
        </ol>
    </x-tarjeta>

    {{-- ===================== 3. Números de la semana ===================== --}}
    {{-- Tres <x-stat-card>: número grande + etiqueta. :valor con dos puntos
         porque se le pasa PHP; etiqueta sin dos puntos porque es texto fijo.
         Cada tarjeta entra en su turno (--i: 2, 3 y 4) y su número cuenta
         desde 0: :contar es el número sin formato y :retraso, cuándo empieza. --}}
    <h2 class="sr-only">Esta semana</h2>
    <div class="mt-2 grid grid-cols-3 gap-2">
        <x-stat-card class="aparecer" style="--i: 2"
                     :valor="$estaSemana['sesiones']" :contar="$estaSemana['sesiones']" :retraso="300"
                     etiqueta="Sesiones" />
        <x-stat-card class="aparecer" style="--i: 3"
                     :valor="Formato::miles($estaSemana['volumen'])" :contar="round($estaSemana['volumen'])" :retraso="340"
                     unidad="kg" etiqueta="Volumen" />
        {{-- Las calorías pasan a Inicio (en Flutter tenían su pestaña). Se
             estiman con el peso del perfil; si aún no hay peso, se muestra el
             tiempo entrenado para no inventar un número (ese no cuenta,
             porque "1h 20" no es un número simple). --}}
        @if ($estaSemana['kcal'] !== null)
            <x-stat-card class="aparecer" style="--i: 4"
                         :valor="Formato::miles($estaSemana['kcal'])" :contar="round($estaSemana['kcal'])" :retraso="380"
                         unidad="kcal" etiqueta="Quemadas" />
        @else
            <x-stat-card class="aparecer" style="--i: 4"
                         :valor="Formato::minutos($estaSemana['minutos'])" etiqueta="Tiempo" />
        @endif
    </div>

    {{-- ===================== 4. Coach IA · Sugerencia de hoy ===================== --}}
    {{-- Tarjeta violeta (variante "ia"): en Go Gym todo lo de la IA va en
         violeta y lo de la base de la app en azul. --}}
    <x-tarjeta variante="ia" class="aparecer mt-3 p-4" style="--i: 5">
        <p class="etiqueta flex items-center gap-1.5 text-ia">
            <x-icono nombre="chispas" class="size-4" /> Coach IA · Sugerencia de hoy
        </p>

        {{-- @if / @else: si hay una rutina que toca, se sugiere esa; si la
             cuenta aún no tiene rutinas, se invita a crear la primera. --}}
        @if ($siguiente)
            {{-- $sugerencia es un string que arma InicioController. Cuando se
                 conecte la IA, el texto cambiará y esta línea seguirá igual. --}}
            <p class="mt-2.5 text-sm leading-relaxed">{{ $sugerencia }}</p>

            <div class="mt-4 flex flex-wrap gap-2">
                {{-- Empezar abre "Registrar entrenamiento" con esta rutina:
                     route('nombre', [...]) arma la URL y agrega ?rutina=ID. --}}
                <a href="{{ route('entrenamientos.create', ['rutina' => $siguiente->id]) }}"
                   class="inline-flex items-center gap-1.5 rounded-[10px] bg-primario-fuerte px-4 py-2.5 text-sm font-semibold text-white hover:bg-primario">
                    <x-icono nombre="play" class="size-3.5" /> Empezar {{ $siguiente->nombre }}
                </a>
                <a href="{{ route('coach') }}"
                   class="inline-flex items-center rounded-[10px] border border-ia-borde px-4 py-2.5 text-sm font-semibold text-texto hover:bg-white/5">
                    Preguntar al coach
                </a>
            </div>
        @else
            <p class="mt-2.5 font-bold leading-tight">Arma tu primera rutina</p>
            <p class="mt-1 text-sm text-tenue">El coach te propone una según tu objetivo, tu nivel y el tiempo que tienes.</p>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('rutinas.generar') }}"
                   class="inline-flex items-center gap-1.5 rounded-[10px] bg-primario-fuerte px-4 py-2.5 text-sm font-semibold text-white hover:bg-primario">
                    <x-icono nombre="chispas" class="size-4" /> Generar rutina
                </a>
                <a href="{{ route('coach') }}"
                   class="inline-flex items-center rounded-[10px] border border-ia-borde px-4 py-2.5 text-sm font-semibold text-texto hover:bg-white/5">
                    Preguntar al coach
                </a>
            </div>
        @endif
    </x-tarjeta>

    {{-- ===================== 5. Últimas sesiones ===================== --}}
    {{-- Título de sección con el enlace "Ver todas" a la derecha. --}}
    <x-titulo-seccion texto="Últimas sesiones" accion="Ver todas" :href="route('entrenamientos.index')"
                      class="aparecer mt-6" style="--i: 6" />

    {{-- @forelse = @foreach con un plan B: si $ultimas viene vacía, se
         muestra lo que está en @empty en vez de dejar el espacio en blanco. --}}
    @forelse ($ultimas as $sesion)
        {{-- Las sesiones siguen la cascada una por una (7, 8, 9…). min(..., 9)
             pone un tope, como en Flutter: una lista larga no tarda más. --}}
        <x-tarjeta :href="route('entrenamientos.index')" class="aparecer mt-2 flex items-center gap-3 px-3 py-2.5"
                   style="--i: {{ min(7 + $loop->index, 9) }}">
            {{-- Inicial del entrenamiento en un cuadrado azul ("P" de Pull B). --}}
            <span class="grid size-9 shrink-0 place-items-center rounded-[10px] bg-primario/14 text-sm font-bold text-primario">
                {{ mb_strtoupper(mb_substr($sesion->nombre, 0, 1)) }}
            </span>
            <div class="min-w-0 flex-1">
                {{-- "Pull B · Espalda": nombre + grupo muscular principal. --}}
                <p class="truncate text-sm font-semibold">{{ $sesion->titulo }}</p>
                {{-- "Viernes · 52 min": día relativo a hoy y duración. --}}
                <p class="text-xs text-tenue">
                    {{ Formato::diaRelativo($sesion->inicio, $hoy) }} · {{ Formato::minutos($sesion->duracion_minutos) }}
                </p>
            </div>
            {{-- A la derecha, el dato que mejor resume la sesión: el volumen si
                 levantó peso, las kcal si fue cardio (y hay peso en el perfil)
                 o, si no, cuántas series hizo. --}}
            <p class="text-xs text-tenue">
                @if ($sesion->volumen > 0)
                    {{ Formato::miles($sesion->volumen) }} kg
                @elseif ($user->peso_kg)
                    {{ Formato::miles($sesion->kcalEstimadas($user->peso_kg)) }} kcal
                @else
                    {{ $sesion->series->count() }} series
                @endif
            </p>
        </x-tarjeta>
    @empty
        <p class="aparecer py-4 text-center text-sm text-tenue" style="--i: 7">Aún no terminas ningún entrenamiento.</p>
    @endforelse
</x-app-layout>
