import Alpine from 'alpinejs';

window.Alpine = Alpine;

/*
 * Directiva x-contar: el número cuenta desde 0 hasta su valor al cargar la
 * página (como NumeroAnimado en la versión de Flutter).
 *
 * Uso en Blade:
 *     <span x-data x-contar.300="8240">8.240</span>
 *     <p x-data x-contar.350="12">12 días seguidos</p>
 *
 *   - El valor entre comillas es el número final, sin formato.
 *   - Solo cambia el primer número del texto; lo demás ("días seguidos")
 *     queda igual mientras cuenta.
 *   - El modificador (.300) es el retraso en milisegundos antes de empezar,
 *     para que cuente cuando su tarjeta ya terminó de aparecer.
 *   - El texto de adentro (8.240) es el que manda el servidor: si JavaScript
 *     no carga, o la persona pidió reducir el movimiento, se queda ese.
 */
Alpine.directive('contar', (el, { expression, modifiers }, { cleanup }) => {
    const final = Number(expression);
    const sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Si el valor no es un número o no se debe animar, no se toca el texto.
    if (!Number.isFinite(final) || final === 0 || sinMovimiento) {
        return;
    }

    const retraso = Number(modifiers[0]) || 0;
    // Corta: el número llega rápido y no distrae.
    const duracion = 700;
    // El texto original, para dejarlo exacto al terminar (con su formato).
    const textoFinal = el.textContent;

    // Punto de miles a la chilena escrito a mano: Intl en español no agrupa
    // los números de 4 cifras (daría "8240" en vez de "8.240").
    const formatear = (n) => String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    // Escribe el texto original con el primer número cambiado por "n".
    // /\d[\d.]*/ calza una cifra seguida de más cifras o puntos ("8.240").
    const mostrar = (n) => {
        el.textContent = textoFinal.replace(/\d[\d.]*/, formatear(n));
    };

    // easeOutCubic: sube rápido al principio y frena al llegar.
    const curva = (t) => 1 - Math.pow(1 - t, 3);

    let cuadro = null;
    let inicio = null;

    // requestAnimationFrame llama a "paso" en cada cuadro que dibuja el
    // navegador (~60 por segundo) con la hora actual en milisegundos.
    const paso = (ahora) => {
        inicio ??= ahora;
        const t = Math.min(1, (ahora - inicio) / duracion);
        if (t < 1) {
            mostrar(final * curva(t));
            cuadro = requestAnimationFrame(paso);
        } else {
            // Al final se deja el texto tal cual lo mandó el servidor.
            el.textContent = textoFinal;
        }
    };

    // Parte en 0 y, pasado el retraso, empieza a contar.
    mostrar(0);
    const espera = setTimeout(() => {
        cuadro = requestAnimationFrame(paso);
    }, retraso);

    // Si Alpine saca el elemento de la página, se detiene todo.
    cleanup(() => {
        clearTimeout(espera);
        cancelAnimationFrame(cuadro);
    });
});

Alpine.start();
