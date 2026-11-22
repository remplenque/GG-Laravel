/*
 * Búsqueda en vivo de la biblioteca: al escribir, pide la misma página con
 * ?q=… (fetch) y reemplaza solo la lista, el total y los chips. La URL se
 * actualiza para poder compartirla o recargarla. Sin JavaScript, el
 * buscador es un formulario GET normal.
 */
const PARTES = ['resultados', 'total-resultados', 'chips-grupo'];

export default () => ({
    cargando: false,
    _espera: null,
    _controlador: null,

    buscar() {
        clearTimeout(this._espera);
        this._espera = setTimeout(() => this.cargar(), 280);
    },

    async cargar() {
        const form = this.$refs.form;
        const params = new URLSearchParams(new FormData(form));
        for (const [clave, valor] of [...params]) {
            if (valor === '') params.delete(clave);
        }
        const url = form.action + (params.size ? `?${params}` : '');

        this._controlador?.abort();
        this._controlador = new AbortController();
        this.cargando = true;

        try {
            const respuesta = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: this._controlador.signal,
            });
            const doc = new DOMParser().parseFromString(await respuesta.text(), 'text/html');
            for (const id of PARTES) {
                const nuevo = doc.getElementById(id);
                if (nuevo) {
                    document.getElementById(id)?.replaceWith(nuevo);
                }
            }
            history.replaceState(history.state, '', url);
        } catch (error) {
            if (error.name !== 'AbortError') {
                form.submit();
            }
        } finally {
            this.cargando = false;
        }
    },
});
