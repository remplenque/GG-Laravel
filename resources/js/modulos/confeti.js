/*
 * Confeti liviano en un <canvas>, sin librerías. Se usa al guardar un
 * entrenamiento y al batir una marca. Respeta "reducir movimiento".
 */
const COLORES = ['#4c8dff', '#2f6bff', '#a78bfa', '#7c5cf5', '#4ade80', '#fb923c', '#e6ebf5'];

export function confeti({ cantidad = 140, origenY = 0.35 } = {}) {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const canvas = document.createElement('canvas');
    canvas.setAttribute('aria-hidden', 'true');
    canvas.className = 'pointer-events-none fixed inset-0 z-[90]';
    document.body.appendChild(canvas);

    const ctx = canvas.getContext('2d');
    const escala = window.devicePixelRatio || 1;
    const ajustar = () => {
        canvas.width = innerWidth * escala;
        canvas.height = innerHeight * escala;
        ctx.setTransform(escala, 0, 0, escala, 0, 0);
    };
    ajustar();

    const piezas = Array.from({ length: cantidad }, (_, i) => {
        const lado = i % 2 === 0 ? -1 : 1;
        const angulo = (-Math.PI / 2) + lado * (Math.random() * 0.9 + 0.15);
        const velocidad = 9 + Math.random() * 9;

        return {
            x: innerWidth / 2 + lado * Math.random() * 40,
            y: innerHeight * origenY,
            vx: Math.cos(angulo) * velocidad,
            vy: Math.sin(angulo) * velocidad,
            giro: Math.random() * Math.PI,
            vGiro: (Math.random() - 0.5) * 0.3,
            ancho: 6 + Math.random() * 6,
            alto: 8 + Math.random() * 8,
            color: COLORES[i % COLORES.length],
            vida: 1,
        };
    });

    let ultimo = performance.now();
    const cuadro = (ahora) => {
        const dt = Math.min(2, (ahora - ultimo) / 16.67);
        ultimo = ahora;
        ctx.clearRect(0, 0, innerWidth, innerHeight);

        let vivas = 0;
        for (const p of piezas) {
            p.vy += 0.32 * dt;
            p.vx *= 0.985;
            p.x += p.vx * dt;
            p.y += p.vy * dt;
            p.giro += p.vGiro * dt;
            if (p.y > innerHeight * 0.6) p.vida -= 0.012 * dt;
            if (p.vida <= 0 || p.y > innerHeight + 40) continue;
            vivas++;

            ctx.save();
            ctx.globalAlpha = Math.max(0, p.vida);
            ctx.translate(p.x, p.y);
            ctx.rotate(p.giro);
            ctx.scale(1, Math.cos(p.giro * 2));
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.ancho / 2, -p.alto / 2, p.ancho, p.alto);
            ctx.restore();
        }

        if (vivas > 0) {
            requestAnimationFrame(cuadro);
        } else {
            canvas.remove();
        }
    };

    requestAnimationFrame(cuadro);
}
