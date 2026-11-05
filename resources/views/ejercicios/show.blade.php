{{-- Vista 06 · Detalle de ejercicio: cómo se hace, marcas y acceso al coach. Pendiente. --}}
<x-app-layout :titulo="$ejercicio->nombre">
    <x-encabezado :titulo="$ejercicio->nombre" etiqueta="Biblioteca de ejercicios" :subtitulo="$ejercicio->grupo" :atras="url()->previous(route('ejercicios.index'))" />

    <x-en-construccion vista="Vista 06 · Detalle de ejercicio" />
</x-app-layout>
