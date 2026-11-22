# Go Gym · Laravel

Versión web (mobile-first) de Go Gym para la Unidad 3. Mantiene la idea, el diseño
y el modelo de datos de la versión en Flutter (`Previous work/`) y suma el Coach IA.
La propuesta y el mockup están en `Documentación/`.

**En vivo:** https://go-gym-blond.vercel.app (botón "Entrar con la cuenta demo" en el login).

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

Para activar la IA real, agrega al `.env` al menos una clave. Con ambas, Groq
es el proveedor principal y Gemini queda como respaldo automático:

```dotenv
GROQ_API_KEY=
GEMINI_API_KEY=
```

Sin claves, la app sigue funcionando con las respuestas por reglas para poder
trabajar localmente y correr las pruebas sin consumir cuota.

Abrir http://localhost:8000 e iniciar sesión con la cuenta demo
(`GOGYM_DEMO_EMAIL` / `GOGYM_DEMO_PASSWORD` en `.env`). Las sesiones demo son relativas
al día en que se corre el seeder: `php artisan db:seed --class=DemoSeeder` la deja al día.

```bash
php artisan test
```

## Interfaz

- Mobile-first con barra inferior; en pantallas grandes, barra lateral y columna más ancha.
- Transiciones entre páginas (View Transitions API), entradas en cascada, números que
  cuentan, avisos flotantes (toasts) y confeti al guardar o batir un récord. Todo respeta
  "reducir movimiento".
- Coach IA sin recargar la página (fetch + JSON), búsqueda en vivo en la biblioteca y
  pantalla de espera mientras la IA arma una rutina.
- Registrar entrenamiento: cronómetro de la sesión, serie "hecha" que copia los valores a
  la siguiente, temporizador de descanso (vibra y suena), y la marca de la última vez como
  sugerencia en cada serie.
- Perfil editable (objetivo, nivel, peso, días, horario, contraseña), borrar la cuenta con
  todos sus datos, mapa de constancia de 12 semanas, volumen semanal y mejores marcas.
- Páginas de error propias (404, 403, 419, 429, 500, 503) y manifest para instalarla como app.

## Deploy en Vercel

Vercel corre PHP con el runtime comunitario `vercel-php@0.9.0` (PHP 8.5):

- `api/index.php` es la entrada; `public/` se sirve como estático (`vercel.json`).
- Las cachés de Laravel y las vistas compiladas van a `/tmp` (variables en `vercel.json`).
- La base de datos es el proyecto de Supabase, en el schema **`gogym`** (no en `public`,
  que expone la API REST y ya tiene las tablas de Flutter) con el rol `gogym_app`, que solo
  tiene acceso a ese schema. Conexión por el *Session pooler* (IPv4).
- En cada deploy, el script `vercel` de `composer.json` crea el schema si falta, migra y
  corre los seeders (la cuenta demo queda al día).
- Variables en Vercel: `APP_KEY`, `APP_URL` y `DATABASE_URL` (secretas). Para la IA real,
  agregar `GROQ_API_KEY` y/o `GEMINI_API_KEY` y volver a desplegar.

```bash
npm run build
vercel deploy --prod
```

## Estructura

```
app/
├── Http/Controllers/
│   ├── InicioController.php        # 01 · Inicio y sugerencia IA con respaldo local
│   ├── RutinaController.php        # Rutinas, detalle, 03 · Generar, 04 · Propuesta, guardar y editar (hecho)
│   ├── CoachController.php         # 02 · Coach IA y conversaciones guardadas por cuenta
│   ├── EntrenamientoController.php # Historial y CRUD de la vista 05
│   ├── EjercicioController.php     # Biblioteca, 06 · Detalle y Explorar en wger (hecho)
│   ├── PerfilController.php        # Perfil (hecho)
│   └── Auth/                       # Laravel Breeze
├── Ai/Agents/                      # CoachAgent, RutinaAgent y SugerenciaAgent
├── Http/Requests/                  # Validación de rutinas y entrenamientos
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
├── components/                     # app-layout, barra-navegacion, barra-lateral, tarjeta, aviso (toast), cargando-ia…
├── errors/                         # 404, 403, 419, 429, 500, 503
├── inicio.blade.php
├── rutinas/                        # index, show, generar, propuesta, editar (hechas)
├── ejercicios/                     # index, show, wger (hechas)
├── perfil/                         # show y editar
├── coach/                          # index (hecha)
├── entrenamientos/                 # registrar, editar e historial
└── auth/                           # Breeze, con el diseño de Go Gym
```

## Rutina con IA

`/rutinas/generar` (vista 03) → `POST rutinas/generar` → `/rutinas/propuesta` (vista 04)
→ Regenerar o Guardar. La propuesta vive en la sesión hasta guardarla; al guardar,
cada día pasa a ser una rutina y entra a la rotación de Inicio y Perfil.

`App\Support\PropuestaRutina` arma la lista de ejercicios permitidos (según equipo y
nivel), `RutinaAgent` devuelve la propuesta con salida estructurada y Laravel vuelve a
validarla antes de mostrarla. Si la salida no cumple el contrato se reintenta una vez.
Sin claves de IA se usa el mismo generador local por reglas.

## Coach IA

`/coach` (vista 02) → `POST coach` (con throttle) → vuelve a `/coach` con la pregunta y
la respuesta. El `+` del encabezado (`DELETE coach`) borra la conversación. Desde el
detalle de un ejercicio se llega con `?pregunta=…` y el texto queda escrito en el campo.
Si la pregunta nombra un ejercicio del catálogo, la respuesta trae "Ver ficha".

`App\Support\Coach` tiene las **instrucciones del CoachAgent** (rol, seguridad y
formato). Solo se envían nivel y objetivo, nunca nombre ni correo. `laravel/ai` guarda
los mensajes en `agent_conversations` y `agent_conversation_messages`, asociados a la
cuenta; el botón `+` elimina la conversación activa. Si ambos proveedores fallan, el
controlador muestra un aviso en vez de un error 500.

## Entrenamientos

`/entrenamientos/registrar` permite elegir una rutina o un ejercicio libre y registrar
peso, repeticiones y RPE por serie. El historial permite editar y borrar solo las
sesiones de la cuenta autenticada. Los colores de la marca están como tokens en
`resources/css/app.css` (`bg-tarjeta`, `text-primario`, `text-ia`…).
