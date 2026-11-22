/*
 * Directiva x-contar: el número cuenta desde 0 hasta su valor al cargar la
 * página (como NumeroAnimado en la versión de Flutter).
 *
 * Uso en Blade:
 *     <span x-data x-contar.300="8240">8.240</span>
 *     <p x-data x-contar.350="12">12 días seguidos</p>
 *
 *   - El valor entre comillas es el número final, sin formato.
 *   - Solo cambia el primer número del texto; lo demás queda igual.
 *   - El modificador (.300) es el retraso en milisegundos antes de empezar.
 *   - El texto de adentro lo manda el servidor: si JavaScript no carga, o la
 *     persona pidió reducir el movimiento, se queda ese.
 */
export default function contar(el, { expression, modifiers }, { cleanup }) {
    const final = Number(expression);
    const sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!Number.isFinite(final) || final === 0 || sinMovimiento) {
        return;
    }

    const retraso = Number(modifiers[0]) || 0;
    const duracion = 800;
    const textoFinal = el.textContent;

    // Punto de miles a la chilena: Intl en español no agrupa 4 cifras.
    const formatear = (n) => String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const mostrar = (n) => {
        el.textContent = textoFinal.replace(/\d[\d.]*/, formatear(n));
    };
    const curva = (t) => 1 - Math.pow(1 - t, 3);

    let cuadro = null;
    let inicio = null;

    const paso = (ahora) => {
        inicio ??= ahora;
        const t = Math.min(1, (ahora - inicio) / duracion);
        if (t < 1) {
            mostrar(final * curva(t));
            cuadro = requestAnimationFrame(paso);
        } else {
            el.textContent = textoFinal;
        }
    };

    mostrar(0);
    const espera = setTimeout(() => {
        cuadro = requestAnimationFrame(paso);
    }, retraso);

    cleanup(() => {
        clearTimeout(espera);
        cancelAnimationFrame(cuadro);
    });
}
