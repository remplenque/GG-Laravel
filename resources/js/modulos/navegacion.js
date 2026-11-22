/*
 * Barra de progreso arriba de la pantalla mientras carga la página siguiente
 * (al tocar un enlace interno o enviar un formulario). En una app de varias
 * páginas, sin esto el toque parece "no hacer nada" hasta que llega la
 * respuesta, sobre todo cuando la IA tarda unos segundos.
 */
let barra = null;

function mostrar() {
    if (barra) {
        return;
    }
    barra = document.createElement('div');
    barra.setAttribute('aria-hidden', 'true');
    barra.className = 'pointer-events-none fixed inset-x-0 top-0 z-[100] h-0.5';
    barra.innerHTML =
        '<div class="progreso-nav h-full bg-gradient-to-r from-primario via-primario to-ia shadow-[0_0_12px_var(--color-primario)]"></div>';
    document.body.appendChild(barra);
}

function ocultar() {
    barra?.remove();
    barra = null;
}

function esNavegacionInterna(enlace, evento) {
    if (!enlace || evento.defaultPrevented || evento.button !== 0) return false;
    if (evento.metaKey || evento.ctrlKey || evento.shiftKey || evento.altKey) return false;
    if (enlace.target && enlace.target !== '_self') return false;
    if (enlace.hasAttribute('download')) return false;

    const url = new URL(enlace.href, location.href);
    if (url.origin !== location.origin) return false;
    // Solo cambia el #ancla: no hay navegación real.
    if (url.pathname === location.pathname && url.search === location.search && url.hash) return false;

    return true;
}

export function iniciarProgresoNavegacion() {
    document.addEventListener('click', (evento) => {
        const enlace = evento.target.closest?.('a[href]');
        if (esNavegacionInterna(enlace, evento)) {
            mostrar();
        }
    });

    document.addEventListener('submit', (evento) => {
        // El chat del coach y otros formularios con fetch lo cancelan
        // (defaultPrevented): ahí no hay navegación.
        queueMicrotask(() => {
            if (!evento.defaultPrevented) {
                mostrar();
            }
        });
    });

    // Al volver con "atrás" la página sale de la caché del navegador (bfcache)
    // tal como quedó: con la barra puesta. Se quita.
    window.addEventListener('pageshow', ocultar);
}
