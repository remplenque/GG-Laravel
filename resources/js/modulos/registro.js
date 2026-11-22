/*
 * Registrar entrenamiento (vista 05): lo que pasa en el navegador mientras
 * se entrena.
 *
 *   - Pasos: un ejercicio por pantalla, con "Anterior" y "Siguiente".
 *   - Cronómetro: cuánto lleva la sesión (solo al registrar, no al editar).
 *   - Serie hecha: al marcarla se rellenan los valores sugeridos si estaban
 *     vacíos, se copian a la serie siguiente y arranca el descanso.
 *   - Descanso: cuenta regresiva con el descanso de la rutina; vibra y suena
 *     al terminar. Se puede saltar o sumar 15 s.
 *   - Duración: si no se tocó a mano y se registró en vivo (5 min o más), al
 *     guardar se usa el tiempo del cronómetro.
 */
function dosDigitos(n) {
    return String(n).padStart(2, '0');
}

function reloj(segundos) {
    const h = Math.floor(segundos / 3600);
    const m = Math.floor((segundos % 3600) / 60);
    const s = segundos % 60;

    return h > 0 ? `${h}:${dosDigitos(m)}:${dosDigitos(s)}` : `${dosDigitos(m)}:${dosDigitos(s)}`;
}

function pitido() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        [0, 0.18].forEach((desfase) => {
            const osc = ctx.createOscillator();
            const vol = ctx.createGain();
            osc.frequency.value = 880;
            vol.gain.setValueAtTime(0.0001, ctx.currentTime + desfase);
            vol.gain.exponentialRampToValueAtTime(0.25, ctx.currentTime + desfase + 0.02);
            vol.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + desfase + 0.15);
            osc.connect(vol).connect(ctx.destination);
            osc.start(ctx.currentTime + desfase);
            osc.stop(ctx.currentTime + desfase + 0.16);
        });
    } catch {
        // Sin audio disponible: no pasa nada.
    }
}

export default ({ total = 1, vivo = false, descansos = [] } = {}) => ({
    paso: 0,
    total,
    vivo,
    segundos: 0,
    hechas: {},
    descanso: { activo: false, restante: 0, total: 0 },
    duracionTocada: false,
    _reloj: null,
    _descanso: null,

    init() {
        if (this.vivo) {
            const inicio = Date.now();
            this._reloj = setInterval(() => {
                this.segundos = Math.floor((Date.now() - inicio) / 1000);
            }, 1000);
        }

        // Si la validación falló, se abre el primer ejercicio con error.
        const conError = this.$root.querySelector('[data-paso-error]');
        if (conError) {
            this.paso = Number(conError.dataset.pasoError) || 0;
        }
    },

    destroy() {
        clearInterval(this._reloj);
        clearInterval(this._descanso);
    },

    get cronometro() {
        return reloj(this.segundos);
    },

    get progreso() {
        return ((this.paso + 1) / this.total) * 100;
    },

    irA(paso) {
        this.paso = Math.max(0, Math.min(this.total - 1, paso));
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    estaHecha(ejercicio, serie) {
        return Boolean(this.hechas[`${ejercicio}-${serie}`]);
    },

    marcar(ejercicio, serie, boton) {
        const clave = `${ejercicio}-${serie}`;
        this.hechas[clave] = !this.hechas[clave];
        if (!this.hechas[clave]) {
            return;
        }

        const fila = boton.closest('[data-fila]');
        const campos = (f) => ({
            kg: f?.querySelector('[data-campo="kg"]'),
            reps: f?.querySelector('[data-campo="reps"]'),
        });

        // Lo sugerido (placeholder con la marca anterior) pasa a ser el valor.
        const actual = campos(fila);
        for (const campo of [actual.kg, actual.reps]) {
            if (campo && campo.value === '' && /^\d+([.,]\d+)?$/.test(campo.placeholder)) {
                campo.value = campo.placeholder.replace(',', '.');
            }
        }

        // La serie siguiente parte con los mismos números.
        const siguiente = campos(fila?.nextElementSibling);
        if (siguiente.kg && siguiente.kg.value === '') siguiente.kg.value = actual.kg?.value ?? '';
        if (siguiente.reps && siguiente.reps.value === '') siguiente.reps.value = actual.reps?.value ?? '';

        navigator.vibrate?.(12);
        this.empezarDescanso(descansos[ejercicio] || 90);
    },

    empezarDescanso(segundos) {
        clearInterval(this._descanso);
        this.descanso = { activo: true, restante: segundos, total: segundos };
        const fin = Date.now() + segundos * 1000;

        this._descanso = setInterval(() => {
            const restante = Math.max(0, Math.round((fin - Date.now()) / 1000));
            this.descanso.restante = restante + (this.descanso.extra ?? 0);
            if (this.descanso.restante <= 0) {
                this.terminarDescanso(true);
            }
        }, 250);
    },

    sumarDescanso(segundos = 15) {
        this.descanso.extra = (this.descanso.extra ?? 0) + segundos;
        this.descanso.total += segundos;
        this.descanso.restante += segundos;
    },

    terminarDescanso(avisar = false) {
        clearInterval(this._descanso);
        this.descanso = { activo: false, restante: 0, total: 0 };
        if (avisar) {
            navigator.vibrate?.([90, 60, 90]);
            pitido();
        }
    },

    get descansoTexto() {
        return reloj(this.descanso.restante);
    },

    get descansoAvance() {
        return this.descanso.total > 0 ? this.descanso.restante / this.descanso.total : 0;
    },

    alEnviar(evento) {
        if (this.vivo && !this.duracionTocada && this.segundos >= 300) {
            const campo = evento.target.querySelector('input[name="duracion"]');
            if (campo) {
                campo.value = Math.round(this.segundos / 60);
            }
        }
    },
});
