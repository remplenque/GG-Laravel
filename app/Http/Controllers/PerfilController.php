<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarPerfilRequest;
use App\Models\Entrenamiento;
use App\Models\EntrenamientoSerie;
use App\Models\Rutina;
use App\Models\User;
use App\Support\Historial;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Laravel\Ai\Models\Conversation;

/**
 * Pestaña Perfil: los datos de la cuenta, el plan de los próximos días y el
 * progreso (mes, mapa de 12 semanas, volumen por semana y mejores marcas).
 * También edita los datos de entrenamiento y permite borrar la cuenta.
 */
class PerfilController extends Controller
{
    /** Semanas del mapa de constancia (12 = un trimestre). */
    private const SEMANAS_MAPA = 12;

    /** Semanas del gráfico de volumen. */
    private const SEMANAS_VOLUMEN = 8;

    /** Perfil, plan de entrenamiento y resumen de progreso de la cuenta. */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $hoy = now()->toImmutable();
        $entrenamientos = $user->entrenamientos()->get();
        $rutinas = $user->rutinas()->with('ejercicios.ejercicio')->get();
        $historial = new Historial($entrenamientos);
        $entrenamientosDelMes = $entrenamientos->filter(
            fn ($entrenamiento) => $entrenamiento->inicio->isSameMonth($hoy)
        );

        return view('perfil.show', [
            'user' => $user,
            'hoy' => $hoy,
            'proximos' => $this->proximosEntrenamientos($user, $rutinas, $entrenamientos, $hoy),
            'progreso' => [
                'sesiones' => $entrenamientosDelMes->count(),
                'minutos' => $entrenamientosDelMes->sum('duracion_minutos'),
                'racha' => $historial->rachaActual($hoy),
                'total' => $entrenamientos->count(),
            ],
            'mapa' => $this->mapaConstancia($entrenamientos, $hoy),
            'volumenSemanal' => $this->volumenSemanal($entrenamientos, $hoy),
            'marcas' => $this->mejoresMarcas($user),
        ]);
    }

    /** GET /perfil/editar: formulario con los datos de entrenamiento y la cuenta. */
    public function editar(Request $request): View
    {
        return view('perfil.editar', [
            'user' => $request->user(),
            'opciones' => config('gogym.rutina_ia'),
        ]);
    }

    /** PUT /perfil: guarda los datos. ActualizarPerfilRequest ya validó todo. */
    public function actualizar(ActualizarPerfilRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['apellido'] ??= '';
        $datos['dias_entrenamiento'] = collect($datos['dias_entrenamiento'] ?? [])
            ->map(fn ($dia) => (int) $dia)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $request->user()->update($datos);

        return redirect()->route('perfil')->with('estado', 'Guardamos tus datos. El coach ya los tiene en cuenta.');
    }

    /**
     * DELETE /perfil: borra la cuenta y todo lo suyo (rutinas y
     * entrenamientos caen en cascada; las conversaciones del coach se borran
     * a mano porque laravel/ai no usa clave foránea). Pide la contraseña.
     */
    public function eliminar(Request $request): RedirectResponse
    {
        $request->validateWithBag('eliminarCuenta', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Primero se cierra la sesión: logout() guarda el remember_token en el
        // usuario y, si ya estuviera borrado, volvería a insertarlo.
        Auth::logout();

        DB::transaction(function () use ($user): void {
            $conversaciones = Conversation::query()
                ->where('participant_type', Conversation::participantType($user))
                ->where('participant_id', Conversation::participantKey($user));

            DB::table(config('ai.conversations.tables.messages', 'agent_conversation_messages'))
                ->whereIn('conversation_id', (clone $conversaciones)->select('id'))
                ->delete();
            $conversaciones->delete();

            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Tu cuenta y tus datos fueron eliminados.');
    }

    /**
     * Arma las tres próximas fechas del plan y rota las rutinas disponibles.
     * Si hoy ya se entrenó, el calendario parte desde el siguiente día elegido.
     *
     * @param  Collection<int, Rutina>  $rutinas
     * @param  Collection<int, Entrenamiento>  $entrenamientos
     * @return list<array{fecha: CarbonInterface, rutina: ?Rutina}>
     */
    private function proximosEntrenamientos(
        User $user,
        Collection $rutinas,
        Collection $entrenamientos,
        CarbonInterface $hoy,
    ): array {
        $disponibles = $rutinas
            ->filter(fn (Rutina $rutina) => $rutina->ejercicios->isNotEmpty())
            ->values();
        $siguiente = Rutina::siguiente($rutinas, $entrenamientos);
        $indiceInicial = $siguiente === null
            ? 0
            : $disponibles->search(fn (Rutina $rutina) => $rutina->is($siguiente));
        $indiceInicial = $indiceInicial === false ? 0 : $indiceInicial;
        $entrenoHoy = $entrenamientos->contains(
            fn ($entrenamiento) => $entrenamiento->inicio->isSameDay($hoy)
        );

        $proximos = [];
        for ($desplazamiento = 0; $desplazamiento <= 21 && count($proximos) < 3; $desplazamiento++) {
            $fecha = $hoy->toImmutable()->startOfDay()->addDays($desplazamiento);
            if (! $user->esDiaEntrenamiento($fecha) || ($desplazamiento === 0 && $entrenoHoy)) {
                continue;
            }

            $proximos[] = [
                'fecha' => $fecha,
                'rutina' => $disponibles->isEmpty()
                    ? null
                    : $disponibles[($indiceInicial + count($proximos)) % $disponibles->count()],
            ];
        }

        return $proximos;
    }

    /**
     * Mapa de constancia: las últimas 12 semanas, de lunes a domingo, con
     * cuántas sesiones hubo cada día (0, 1, 2+) para pintar la intensidad.
     *
     * @param  Collection<int, Entrenamiento>  $entrenamientos
     * @return list<list<array{fecha: CarbonImmutable, sesiones: int, futuro: bool}>>
     */
    private function mapaConstancia(Collection $entrenamientos, CarbonImmutable $hoy): array
    {
        $porDia = $entrenamientos->countBy(fn (Entrenamiento $e) => $e->inicio->toDateString());
        $inicio = $hoy->startOfWeek(CarbonInterface::MONDAY)->subWeeks(self::SEMANAS_MAPA - 1);

        $semanas = [];
        for ($s = 0; $s < self::SEMANAS_MAPA; $s++) {
            $semana = [];
            for ($d = 0; $d < 7; $d++) {
                $fecha = $inicio->addDays($s * 7 + $d);
                $semana[] = [
                    'fecha' => $fecha,
                    'sesiones' => $porDia[$fecha->toDateString()] ?? 0,
                    'futuro' => $fecha->gt($hoy->endOfDay()),
                ];
            }
            $semanas[] = $semana;
        }

        return $semanas;
    }

    /**
     * Volumen (kg × reps) de cada una de las últimas 8 semanas, de la más
     * antigua a la actual. Solo se cargan las series de esas semanas.
     *
     * @param  Collection<int, Entrenamiento>  $entrenamientos
     * @return list<array{desde: CarbonImmutable, volumen: float}>
     */
    private function volumenSemanal(Collection $entrenamientos, CarbonImmutable $hoy): array
    {
        $lunesActual = $hoy->startOfWeek(CarbonInterface::MONDAY);
        $desde = $lunesActual->subWeeks(self::SEMANAS_VOLUMEN - 1);
        $recientes = $entrenamientos->filter(fn (Entrenamiento $e) => $e->inicio->gte($desde))->values();
        $recientes->load('series');

        return collect(range(0, self::SEMANAS_VOLUMEN - 1))->map(function (int $i) use ($desde, $recientes) {
            $lunes = $desde->addWeeks($i);
            $volumen = $recientes
                ->filter(fn (Entrenamiento $e) => $e->inicio->gte($lunes) && $e->inicio->lt($lunes->addWeek()))
                ->sum('volumen');

            return ['desde' => $lunes, 'volumen' => (float) $volumen];
        })->all();
    }

    /**
     * Las 4 mejores marcas de la cuenta: la carga más alta en cada ejercicio,
     * ordenadas de mayor a menor. Una sola consulta agrupada.
     *
     * @return \Illuminate\Support\Collection<int, EntrenamientoSerie>
     */
    private function mejoresMarcas(User $user): \Illuminate\Support\Collection
    {
        return EntrenamientoSerie::query()
            ->select('ejercicio_id')
            ->selectRaw('MAX(kg) as mejor')
            ->whereNotNull('kg')
            ->where('kg', '>', 0)
            ->whereHas('entrenamiento', fn ($consulta) => $consulta->where('user_id', $user->id))
            ->groupBy('ejercicio_id')
            ->orderByDesc('mejor')
            ->limit(4)
            ->with('ejercicio:id,nombre,grupo')
            ->get();
    }
}
