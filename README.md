# Lextrack

Aplicación interna para gestionar contratos, aprobaciones y marcas dentro de Corporación Medifarma. El proyecto está basado en **Laravel 12**, usa **Vite + Tailwind** en el frontend y la autenticación ocurre exclusivamente a través de Microsoft (Azure AD).

## Requisitos
- PHP 8.2+
- Composer
- Node.js 20+ y npm
- SQLite, MySQL o Postgres (según `.env`)

## Configuración local
1. Clona el repositorio y duplica el archivo `.env.example` como `.env`. Completa las variables de Microsoft (`MICROSOFT_*`), base de datos y correo.
2. Instala dependencias:
   ```bash
   composer install
   npm install
   ```
3. Genera la clave y ejecuta las migraciones con seeders (solo roles/permisos/categorías):
   ```bash
   php artisan key:generate
   php artisan migrate --seed
   ```
4. Compila los assets:
   ```bash
   npm run dev # o npm run build para producción
   ```
5. Levanta el servidor (`php artisan serve`). El primer usuario que inicie sesión via Microsoft recibirá automáticamente el rol `admin`.

## Deploy limpio a producción
1. Sube el código (sin `node_modules`, `vendor`, `storage/app/public`, `public/build`).
2. En el servidor instala dependencias y compila assets: `composer install --optimize-autoloader --no-dev` y `npm ci && npm run build`.
3. Copia `.env`, ejecuta `php artisan migrate:fresh --seed --force` para cargar roles, permisos y categorías iniciales.
4. Crea el enlace de storage (`php artisan storage:link`) y cachea configuración/rutas (`php artisan config:cache`, `php artisan route:cache`).
5. El primer inicio de sesión Microsoft promoverá al usuario a `admin`; desde la interfaz se pueden crear el resto de datos (templates, usuarios, etc.).

## Scripts útiles
- `composer dev`: ejecuta backend (`artisan serve`, cola, logs) y `npm run dev` usando `concurrently`.
- `composer test`: limpia la caché y corre `php artisan test`.
- `composer setup`: instala dependencias, genera `.env`, aplica migraciones y compila assets para un entorno nuevo.

## Notas
- El módulo de contratos ahora soporta versiones observadas: cada vez que se marca "Observar" se clona la versión vigente, adjuntos incluidos, y se notifica a los involucrados.
- Solo roles `abogado` pueden asignar asesores, configurar aprobadores y cargar documentos firmados.
- Los seeders oficiales (`SpatieRolePermissionSeeder`, `ContractCategorySeeder`) son la fuente de verdad para roles/permisos y categorías. Añade seeders personalizados si necesitas datos adicionales.
