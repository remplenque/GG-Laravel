<?php

namespace App\Http\Controllers;

use App\Ai\Agents\CoachAgent;
use App\Models\Ejercicio;
use App\Support\Coach;
use App\Support\ProveedoresIa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
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

    private const CONVERSACION = 'coach.conversacion_id';

    private const CONVERSACION_VACIA = 'coach.conversacion_vacia';

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
                $this->idConversacion($request),
            );
        } catch (Throwable $error) {
            // Cuota agotada, proveedor caído, respuesta vacía… Se registra en
            // el log y la persona ve un aviso con su pregunta aún escrita.
            report($error);

            return back()
                ->withInput()
                ->with('error', 'El coach no pudo responder ahora. Espera un momento y vuelve a intentarlo.');
        }

        if ($coach->ultimaConversacionId() !== null) {
            $request->session()->put(self::CONVERSACION, $coach->ultimaConversacionId());
            $request->session()->forget([self::CONVERSACION_VACIA, self::SESION]);
        } else {
            $mensajes[] = ['rol' => 'usuario', 'texto' => $pregunta, 'ejercicio_id' => null];
            $mensajes[] = ['rol' => 'coach', 'texto' => $respuesta, 'ejercicio_id' => $ejercicio?->id];
            $this->guardarMensajes($request, $mensajes);
        }

        return redirect()->route('coach');
    }

    /**
     * "Nueva conversación" (el + del encabezado): borra la actual. Cumple
     * con "permitir borrar las conversaciones" de la propuesta.
     */
    public function nueva(Request $request): RedirectResponse
    {
        $id = $this->idConversacion($request);
        if ($id !== null) {
            DB::transaction(function () use ($id, $request): void {
                $conversacion = $this->conversacionDe($request, $id);
                if ($conversacion === null) {
                    return;
                }

                $conversacion->messages()->delete();
                $conversacion->delete();
            });
        }

        $request->session()->forget([self::SESION, self::CONVERSACION]);
        $request->session()->put(self::CONVERSACION_VACIA, true);

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
        $id = $this->idConversacion($request);
        $conversacion = $id === null ? null : $this->conversacionDe($request, $id);

        if ($conversacion !== null) {
            $ejercicioActual = null;
            $coach = app(Coach::class);

            return $conversacion->messages()
                ->where('agent', CoachAgent::class)
                ->oldest()
                ->get(['role', 'content'])
                ->map(function (ConversationMessage $mensaje) use ($coach, &$ejercicioActual): array {
                    if ($mensaje->role === 'user') {
                        $ejercicioActual = $coach->ejercicioMencionado($mensaje->content)?->id;

                        return ['rol' => 'usuario', 'texto' => $mensaje->content, 'ejercicio_id' => null];
                    }

                    $resultado = [
                        'rol' => 'coach',
                        'texto' => $mensaje->content,
                        'ejercicio_id' => $ejercicioActual,
                    ];
                    $ejercicioActual = null;

                    return $resultado;
                })
                ->values()
                ->all();
        }

        return $request->session()->get(self::SESION, []);
    }

    private function idConversacion(Request $request): ?string
    {
        $id = $request->session()->get(self::CONVERSACION);
        if (is_string($id) || $request->session()->get(self::CONVERSACION_VACIA) === true) {
            return $id;
        }

        if (! app(ProveedoresIa::class)->configurada()) {
            return null;
        }

        $user = $request->user();
        $id = Conversation::query()
            ->where('participant_type', Conversation::participantType($user))
            ->where('participant_id', Conversation::participantKey($user))
            ->whereHas('messages', fn ($consulta) => $consulta->where('agent', CoachAgent::class))
            ->latest('updated_at')
            ->value('id');

        if (is_string($id)) {
            $request->session()->put(self::CONVERSACION, $id);
        }

        return $id;
    }

    private function conversacionDe(Request $request, string $id): ?Conversation
    {
        $user = $request->user();

        return Conversation::query()
            ->whereKey($id)
            ->where('participant_type', Conversation::participantType($user))
            ->where('participant_id', Conversation::participantKey($user))
            ->first();
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
