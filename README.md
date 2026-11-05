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
│   ├── InicioController.php        # 01 · Inicio (hecho)
│   ├── RutinaController.php        # Rutinas y su detalle (hecho); 03 · Generar (pendiente)
│   ├── CoachController.php         # 02 · Coach IA (pendiente)
│   ├── EntrenamientoController.php # Historial y 05 · Registrar (pendiente)
│   ├── EjercicioController.php     # Biblioteca y 06 · Detalle (pendiente)
│   ├── PerfilController.php        # Perfil (pendiente)
│   └── Auth/                       # Laravel Breeze
├── Models/                         # User, Ejercicio, Rutina, RutinaEjercicio, Entrenamiento, EntrenamientoSerie
├── Policies/RutinaPolicy.php       # Cada cuenta ve solo sus rutinas
└── Support/
    ├── Historial.php               # Racha, récord y semana (antes EntrenamientosProvider)
    └── Formato.php                 # Miles, minutos y fechas en español
config/gogym.php                    # Ajustes de la app (URL de imágenes, MET, cuenta demo)
database/
├── data/ejercicios.json            # Catálogo RepDB (601 ejercicios) que usaba Flutter
└── seeders/                        # EjercicioSeeder, DemoSeeder
resources/views/
├── components/                     # app-layout, barra-navegacion, tarjeta, stat-card, icono…
├── inicio.blade.php
├── rutinas/                        # index, show (hechas) · generar (pendiente)
├── coach/ entrenamientos/ ejercicios/ perfil/   # pendientes
└── auth/                           # Breeze, con el diseño de Go Gym
```

Las vistas pendientes muestran `<x-en-construccion>`. Para trabajar una, reemplaza ese
componente en su archivo de `resources/views` y completa el método de su controlador.
Los colores de la marca están como tokens en `resources/css/app.css` (`bg-tarjeta`,
`text-primario`, `text-ia`…).
