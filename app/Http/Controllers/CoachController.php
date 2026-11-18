<?php

namespace App\Http\Controllers;

use App\Models\Ejercicio;
use App\Support\Coach;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Vista 02 · Coach IA (chat).
 *
 * La conversación vive en la sesión (como la propuesta de rutina) hasta que
 * se conecte el CoachAgent de laravel/ai, que la guarda en la base de datos
 * por cuenta. Ese cambio queda en dos métodos: Coach::responder() (quién
 * contesta) y mensajes()/guardarMensajes() de aquí abajo (dónde se lee y
 * guarda la conversación). La vista y las rutas no cambian.
 */
class CoachController extends Controller
{
    /** Clave de la sesión con la conversación. */
    private const SESION = 'coach.mensajes';

    /** Tope de mensajes guardados: la sesión no crece sin fin. */
    private const MAX_MENSAJES = 40;

    /**
     * El chat. Si llega ?pregunta=… (desde el detalle de un ejercicio), queda
     * escrita en el campo de texto, lista para enviar.
     */
    public function index(Request $request, Coach $coach): View
    {
        $mensajes = $this->mensajes($request);

        // Nombres de los ejercicios que aparecen en la conversación, en una
        // sola consulta, para el botón "Ver ficha" de cada respuesta.
        $ejercicios = Ejercicio::query()
            ->whereIn('id', array_filter(array_column($mensajes, 'ejercicio_id')))
            ->pluck('nombre', 'id');

        return view('coach.index', [
            'mensajes' => $mensajes,
            'ejercicios' => $ejercicios,
            'sugerencias' => $coach->sugerencias(),
            'pregunta' => old('pregunta', (string) $request->query('pregunta', '')),
            'maxPregunta' => Coach::MAX_PREGUNTA,
        ]);
    }

    /** Envía una pregunta y guarda la pregunta y la respuesta. */
    public function preguntar(Request $request, Coach $coach): RedirectResponse
    {
        $datos = $request->validate([
            'pregunta' => ['required', 'string', 'max:'.Coach::MAX_PREGUNTA],
        ]);
        $pregunta = trim($datos['pregunta']);
        $mensajes = $this->mensajes($request);
        $ejercicio = $coach->ejercicioMencionado($pregunta);

        try {
            $respuesta = $coach->responder(
                $request->user(),
                array_slice($mensajes, -Coach::MAX_HISTORIAL),
                $pregunta,
                $ejercicio,
            );
        } catch (Throwable $error) {
            // Cuota agotada, proveedor caído, respuesta vacía… Se registra en
            // el log y la persona ve un aviso con su pregunta aún escrita.
            report($error);

            return back()
                ->withInput()
                ->with('error', 'El coach no pudo responder ahora. Espera un momento y vuelve a intentarlo.');
        }

        $mensajes[] = ['rol' => 'usuario', 'texto' => $pregunta, 'ejercicio_id' => null];
        $mensajes[] = ['rol' => 'coach', 'texto' => $respuesta, 'ejercicio_id' => $ejercicio?->id];
        $this->guardarMensajes($request, $mensajes);

        return redirect()->route('coach');
    }

    /**
     * "Nueva conversación" (el + del encabezado): borra la actual. Cumple
     * con "permitir borrar las conversaciones" de la propuesta.
     */
    public function nueva(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESION);

        return redirect()->route('coach');
    }

    /**
     * La conversación actual.
     *
     * Con laravel/ai: leer los mensajes de la conversación de la cuenta
     * desde sus tablas y devolverlos con esta misma forma.
     *
     * @return list<array{rol: string, texto: string, ejercicio_id: ?string}>
     */
    private function mensajes(Request $request): array
    {
        return $request->session()->get(self::SESION, []);
    }

    /**
     * Guarda la conversación (solo los últimos MAX_MENSAJES).
     *
     * Con laravel/ai: RemembersConversations ya guarda cada mensaje, así que
     * aquí solo queda guardar el ejercicio_id de la respuesta, si se quiere
     * seguir mostrando "Ver ficha".
     */
    private function guardarMensajes(Request $request, array $mensajes): void
    {
        $request->session()->put(self::SESION, array_slice($mensajes, -self::MAX_MENSAJES));
    }
}
