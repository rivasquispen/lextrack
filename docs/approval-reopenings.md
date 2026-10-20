# Reapertura excepcional de una aprobación individual

Acceso: Backend → Configuración → Correcciones de aprobaciones
(`/admin/approval-reopenings`). Exclusivo del rol `admin`, tanto en middleware
como en controlador y servicio. No modifica las tarjetas del contrato.

## Alcance

- Solo contratos con estado `en_aprobacion` y versión vigente no aprobada.
- Debe haber al menos una decisión aprobada con fecha y otra pendiente en esa versión.
- No se permite con documento firmado, firmantes con evidencia de firma ni firmas
  registradas en la tabla histórica `signatures`.
- Contratos aprobados, firmados, observados, creados o asignados quedan fuera.
- Para contratos ya aprobados, usar el procedimiento existente del abogado para
  observar y generar una nueva versión; esta herramienta no lo reemplaza.

La corrección cambia únicamente `approvals.estado` a `pendiente` y
`approvals.aprobado_at` a `NULL` (además del timestamp de actualización automático).
No cambia la asignación, observaciones, otras decisiones, estado del contrato,
documentos, archivos ni versiones. Versión 2 sigue siendo versión 2. No elimina
ni crea versiones y no retrocede al documento de la versión 1.

## Operación y trazabilidad

Buscar por ID, código o título; seleccionar la aprobación, indicar motivo,
abogado coordinador y referencia del correo/ticket/observación. Confirmar la
acción. La coordinación se declara por el admin: el sistema no exige una segunda
aprobación electrónica del abogado.

Se crea un evento en `approval_reopenings`, en la misma transacción que modifica
la aprobación. Incluye IDs, versión, estado y fecha originales, admin, abogado,
motivo y un snapshot para conservar la trazabilidad. Las referencias se guardan
sin borrado en cascada, de modo que eliminar una asignación posteriormente no
borre su auditoría. No hay endpoints para editar ni eliminar eventos.

La bitácora es global y de solo lectura; sigue mostrando eventos de contratos
que posteriormente se aprobaron. Solo la lista de acciones se limita a contratos
elegibles. El historial de documentos no se utiliza como bitácora de decisiones.

El aprobador y el abogado coordinador reciben una notificación por correo después
de confirmar la transacción. Si un correo falla, se conserva la corrección y se
muestra una advertencia para coordinar manualmente. No se notifica a todo el personal.

El endpoint normal de aprobación comparte el bloqueo del contrato y la versión
con la reapertura, relee los datos y rechaza versiones anteriores o evidencia de
firma. Si la última aprobación termina primero, la reapertura queda bloqueada.
La fecha de aprobación enviada desde la pantalla también se compara para impedir
reabrir una decisión más reciente desde un formulario obsoleto.

## Archivos para desplegar

Nuevos:

- `app/Http/Controllers/Admin/ApprovalReopeningController.php`
- `app/Services/ApprovalReopeningService.php`
- `app/Models/ApprovalReopening.php`
- `app/Notifications/ApprovalReopened.php`
- `resources/views/admin/approval-reopenings/index.blade.php`
- `database/migrations/2026_10_20_000000_create_approval_reopenings_table.php`

Modificados:

- `app/Http/Controllers/ContractApprovalController.php`
- `app/Models/Contract.php` (relación con versión vigente)
- `resources/views/components/admin-layout.blade.php`
- `routes/web.php`

Pruebas: `tests/Feature/ApprovalReopeningTest.php`.

## Despliegue

1. Respaldar código y base y validar en staging, especialmente con el motor de BD
   productivo. No ejecutar comandos de pruebas contra la base real.
2. Subir los archivos nuevos de modelo, servicio, controlador, notificación y vista.
3. Ejecutar únicamente la migración nueva, sin alterar tablas existentes:

   ```bash
   php artisan migrate --path=database/migrations/2026_10_20_000000_create_approval_reopenings_table.php --force
   ```

4. Aplicar los cuatro archivos modificados; si difieren en producción, integrar
   los cambios sin sobrescribir ajustes adicionales. No habilitar menú/rutas antes
   de crear la tabla. No cambian dependencias de Composer.
5. Reconstruir Tailwind/Vite con `npm run build` en el entorno de build y desplegar
   `public/build` completo. Limpiar cachés de rutas y vistas según el despliegue:

   ```bash
   php artisan route:clear
   php artisan view:clear
   ```

6. Verificar acceso admin/no admin, contrato aprobado excluido y un caso permitido
   en staging, comprobando que se mantienen documentos, versiones y otras decisiones.
   Considerar que la reapertura envía correos reales en producción.

Para retirar la funcionalidad, restaurar primero código y rutas. Conservar la
tabla de auditoría; no ejecutar rollback de la migración si hay eventos registrados.

## Pruebas aisladas

```bash
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: MAIL_MAILER=array \
  php vendor/bin/phpunit tests/Feature/ApprovalReopeningTest.php
```

La suite usa SQLite en memoria y simula notificaciones/correos. No conecta a las
bases del `.env` ni realiza envíos. Excluye migraciones del módulo de marcas, que
recrean una tabla existente al migrar desde cero, y prepara en la base efímera el
índice que una migración histórica de permisos espera eliminar. No modifica esas
migraciones productivas. No comprueba contención real entre conexiones SQL Server:
validar ese escenario también en staging.

PHP 8.5 puede emitir avisos de deprecación de las constantes PDO MySQL de la
configuración existente; no corresponden a esta funcionalidad.

La ejecución general también incluye cuatro pruebas antiguas de ProfileTest que
esperan editar/eliminar perfiles, aunque ProfileController bloquea esas acciones
explícitamente con 403. No se modifica ese comportamiento como parte del ajuste.
El build se genera correctamente; el entorno local advierte que Node 20.12 está
por debajo de la versión recomendada por Vite. Usar Node compatible en el pipeline.
