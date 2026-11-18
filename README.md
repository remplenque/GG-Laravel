# Go Gym · Laravel

Versión web (mobile-first) de Go Gym para la Unidad 3. Mantiene la idea, el diseño
y el modelo de datos de la versión en Flutter (`Previous work/`) y suma el Coach IA.
La propuesta y el mockup están en `Documentación/`.

## Requisitos

- PHP 8.3+ y Composer (en Windows: [php.new](https://php.new) instala ambos).
- Node 20+ y npm.

## Correr el proyecto

```bash
composer install
npm install
cp .env.example .env        # en Windows: copy .env.example .env
php artisan key:generate
php artisan migrate --seed  # crea database/database.sqlite, el catálogo y la cuenta demo
npm run build               # o `npm run dev` mientras se trabaja en las vistas
php artisan serve
```

Abrir http://localhost:8000 e iniciar sesión con la cuenta demo
(`GOGYM_DEMO_EMAIL` / `GOGYM_DEMO_PASSWORD` en `.env`). Las sesiones demo son relativas
al día en que se corre el seeder: `php artisan db:seed --class=DemoSeeder` la deja al día.

```bash
php artisan test
```

## Estructura

```
app/
├── Http/Controllers/
│   ├── InicioController.php        # 01 · Inicio (hecho; la sugerencia aún es por reglas)
│   ├── RutinaController.php        # Rutinas, detalle, 03 · Generar, 04 · Propuesta, guardar y editar (hecho)
│   ├── CoachController.php         # 02 · Coach IA: chat, preguntar y nueva conversación (hecho; respuestas por reglas)
│   ├── EntrenamientoController.php # Historial y 05 · Registrar (pendiente)
│   ├── EjercicioController.php     # Biblioteca, 06 · Detalle y Explorar en wger (hecho)
│   ├── PerfilController.php        # Perfil (hecho)
│   └── Auth/                       # Laravel Breeze
├── Http/Requests/                  # GenerarRutinaRequest, ActualizarRutinaRequest
├── Models/                         # User, Ejercicio, Rutina, RutinaEjercicio, Entrenamiento, EntrenamientoSerie
├── Policies/RutinaPolicy.php       # Cada cuenta ve, edita y borra solo sus rutinas
└── Support/
    ├── Historial.php               # Racha, récord y semana (antes EntrenamientosProvider)
    ├── Formato.php                 # Miles, minutos y fechas en español
    ├── PropuestaRutina.php         # Rutina con IA: ejercicios permitidos, generar y validar
    ├── Coach.php                   # Coach IA: instrucciones del agente, contexto y responder
    └── Wger.php                    # Cliente de la API de wger (con caché)
config/gogym.php                    # Ajustes de la app (imágenes, MET, biblioteca, rutina con IA, wger, cuenta demo)
database/
├── data/ejercicios.json            # Catálogo RepDB (601 ejercicios, ya traducido al español)
└── seeders/                        # EjercicioSeeder, DemoSeeder
resources/views/
├── components/                     # app-layout, barra-navegacion, tarjeta, stat-card, aviso, icono…
├── inicio.blade.php
├── rutinas/                        # index, show, generar, propuesta, editar (hechas)
├── ejercicios/                     # index, show, wger (hechas)
├── perfil/                         # show (hecha)
├── coach/                          # index (hecha)
├── entrenamientos/                 # pendiente
└── auth/                           # Breeze, con el diseño de Go Gym
```

## Rutina con IA

`/rutinas/generar` (vista 03) → `POST rutinas/generar` → `/rutinas/propuesta` (vista 04)
→ Regenerar o Guardar. La propuesta vive en la sesión hasta guardarla; al guardar,
cada día pasa a ser una rutina y entra a la rotación de Inicio y Perfil.

`App\Support\PropuestaRutina` arma la lista de ejercicios permitidos (según equipo y
nivel), genera la propuesta y la valida antes de mostrarla. **Por ahora `generar()` usa
reglas fijas**: para conectar el `RutinaAgent` de `laravel/ai` basta con cambiar el
cuerpo de ese método (los pasos están documentados ahí); el resto no cambia.

## Coach IA

`/coach` (vista 02) → `POST coach` (con throttle) → vuelve a `/coach` con la pregunta y
la respuesta. El `+` del encabezado (`DELETE coach`) borra la conversación. Desde el
detalle de un ejercicio se llega con `?pregunta=…` y el texto queda escrito en el campo.
Si la pregunta nombra un ejercicio del catálogo, la respuesta trae "Ver ficha".

`App\Support\Coach` tiene las **instrucciones del CoachAgent** (rol, seguridad y
formato), el contexto que se le manda (solo nivel y objetivo, nunca nombre ni correo) y
`responder()`. **Por ahora `responder()` usa reglas fijas** (dudas frecuentes + datos del
catálogo) y la conversación vive en la sesión. Para conectar la IA: cambiar el cuerpo de
`responder()` (pasos documentados ahí) y, para guardar la conversación por cuenta, los
métodos `mensajes()` / `guardarMensajes()` de `CoachController`. Si la IA lanza una
excepción, el controlador ya muestra un aviso en vez de un error 500.

Las vistas pendientes muestran `<x-en-construccion>`. Para trabajar una, reemplaza ese
componente en su archivo de `resources/views` y completa el método de su controlador.
Los colores de la marca están como tokens en `resources/css/app.css` (`bg-tarjeta`,
`text-primario`, `text-ia`…).
