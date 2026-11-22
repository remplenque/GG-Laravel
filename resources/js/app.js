import Alpine from 'alpinejs';

import contar from './modulos/contar';
import { iniciarProgresoNavegacion } from './modulos/navegacion';
import { confeti } from './modulos/confeti';
import coach from './modulos/coach';
import registro from './modulos/registro';
import busqueda from './modulos/busqueda';

window.Alpine = Alpine;
window.confeti = confeti;

/*
 * Go Gym usa Alpine.js para todo lo que pasa en el navegador sin recargar la
 * página. Cada pieza vive en su archivo dentro de ./modulos:
 *
 *   - x-contar:   números que cuentan desde 0 (Inicio, Perfil).
 *   - coach:      el chat del Coach IA con envío sin recargar.
 *   - registro:   cronómetro, series hechas y descanso al registrar.
 *   - busqueda:   búsqueda en vivo de la biblioteca.
 *   - navegación: barra de progreso arriba mientras carga otra página.
 *   - confeti:    celebración al guardar un entrenamiento o batir un récord.
 *
 * Todo es mejora progresiva: sin JavaScript, los formularios y enlaces
 * funcionan igual (Blade + POST normales).
 */
Alpine.directive('contar', contar);
Alpine.data('coach', coach);
Alpine.data('registro', registro);
Alpine.data('busqueda', busqueda);

/* Toast: se cierra solo pasado "duracion" (ms) salvo que sea un error. */
Alpine.data('toast', (duracion = 4500) => ({
    visible: true,
    init() {
        if (duracion > 0) {
            setTimeout(() => (this.visible = false), duracion);
        }
    },
}));

iniciarProgresoNavegacion();

Alpine.start();
