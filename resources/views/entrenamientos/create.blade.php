{{-- Vista 05 · Registrar entrenamiento: series con peso y repeticiones. Pendiente. --}}
<x-app-layout titulo="Registrar entrenamiento">
    <x-encabezado
        :titulo="$rutina?->nombre ?? 'Entrenamiento libre'"
        etiqueta="Registrar entrenamiento"
        :subtitulo="$rutina?->resumen"
        :atras="$rutina ? route('rutinas.show', $rutina) : route('inicio')" />

    <x-en-construccion vista="Vista 05 · Registrar entrenamiento" />
</x-app-layout>
