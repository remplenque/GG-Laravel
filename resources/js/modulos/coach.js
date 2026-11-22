/*
 * Chat del Coach IA sin recargar la página.
 *
 * Cada formulario con data-pregunta (el campo de abajo, las preguntas de
 * ejemplo y las respuestas rápidas) se envía con fetch pidiendo JSON. El
 * servidor (CoachController@preguntar) responde con el HTML de la pregunta y
 * la respuesta, ya armado con el mismo parcial de Blade que usa la página,
 * y aquí solo se agrega al final de la conversación.
 *
 * Mientras tanto, la pregunta aparece al tiro como burbuja y el coach queda
 * "escribiendo…". Sin JavaScript, los mismos formularios hacen un POST
 * normal y la página se recarga con la respuesta.
 */
const MENSAJES_ERROR = {
    419: 'Tu sesión expiró. Recarga la página para seguir conversando.',
    429: 'Hiciste muchas preguntas seguidas. Espera un minuto y vuelve a intentarlo.',
};

export default ({ hayMensajes = false } = {}) => ({
    hayMensajes,
    enviando: false,
    enviado: '',
    error: '',

    init() {
        if (this.hayMensajes) {
            this.$nextTick(() => this.bajar(false));
        }
    },

    bajar(suave = true) {
        window.scrollTo({ top: document.documentElement.scrollHeight, behavior: suave ? 'smooth' : 'auto' });
    },

    async enviar(evento) {
        const form = evento.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.pregunta === undefined) {
            return;
        }
        evento.preventDefault();
        if (this.enviando) {
            return;
        }

        const datos = new FormData(form);
        const pregunta = String(datos.get('pregunta') ?? '').trim();
        if (pregunta === '') {
            return;
        }

        const campo = this.$refs.campo;
        const teniaMensajes = this.hayMensajes;
        this.error = '';
        this.enviado = pregunta;
        this.enviando = true;
        this.hayMensajes = true;
        if (form.contains(campo)) {
            campo.value = '';
        }
        this.$nextTick(() => this.bajar());

        try {
            const respuesta = await fetch(form.action, {
                method: 'POST',
                body: datos,
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await respuesta.json().catch(() => ({}));

            if (!respuesta.ok) {
                this.error = MENSAJES_ERROR[respuesta.status]
                    ?? json.message
                    ?? 'El coach no pudo responder ahora. Espera un momento y vuelve a intentarlo.';
                campo.value = pregunta;
                this.hayMensajes = teniaMensajes;
                return;
            }

            this.$refs.lista.insertAdjacentHTML('beforeend', json.html);
            this.$nextTick(() => this.bajar());
        } catch {
            this.error = 'Sin conexión. Revisa tu internet y vuelve a intentarlo.';
            campo.value = pregunta;
            this.hayMensajes = teniaMensajes;
        } finally {
            this.enviando = false;
            if (window.matchMedia('(pointer: fine)').matches) {
                campo.focus();
            }
        }
    },
});
