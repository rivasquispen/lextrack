# Candados y orden visual de aprobadores

Las asignaciones de `approvals` incorporan `is_unlocked` (booleano, DEFAULT 0) y
`position` (entero sin signo), con índice por versión, posición e ID. No hay tabla
ni historial de bloqueos/desbloqueos.

## Migración y compatibilidad

`2026_11_24_000000_add_lock_and_position_to_approvals.php` habilita únicamente las
asignaciones existentes durante la migración y asigna posiciones por versión e ID,
sin cambiar decisiones, fechas de aprobación, asignación, documentos ni versiones.
Las asignaciones nuevas mantienen el valor predeterminado 0 y se agregan al final.
Una nueva versión formal copia el orden, pero recrea las asignaciones bloqueadas y
pendientes. Las revisiones de documento dentro de la misma versión conservan sus
candados. La reversión excepcional de una aprobación conserva su candado existente.

## Autorización y restricciones

Un abogado puede administrar cualquier contrato; un asesor puede hacerlo únicamente
si `asesor_id` corresponde a su usuario. El rol admin por sí solo no concede esta
función. Esta ampliación no permite a un asesor asignar ni eliminar aprobadores.

Los cambios solo se aceptan para la versión vigente de un contrato creado, asignado,
en aprobación u observado; la versión no puede estar aprobada y no puede haber
evidencia de firma. Un usuario que ya aprobó o tiene `aprobado_at` no puede cambiar
su candado. Se puede volver a bloquear una asignación pendiente.

El candado se valida en el servidor al aprobar. Las revisiones y los adjuntos
comprueban la asignación del usuario en la versión vigente; una asignación bloqueada
no obtiene acceso por ser además el creador o asesor del contrato. Se conservan las
facultades administrativas de carga del abogado y la consulta/descarga existente.
La comprobación de carga se repite bajo bloqueo de contrato antes de escribir.

Los controles envían `version_id`, y las rutas nuevas son:

- `PATCH /contracts/{contract}/approvals/{approval}/lock`: `is_unlocked` 0/1.
- `PATCH /contracts/{contract}/approvals/{approval}/position`: `direction` left/right.

Se revalidan rol/asignación, contrato, versión y decisión bajo transacción y bloqueo.
Las flechas solo cambian posiciones y conservan decisiones, candados y fechas de
asignación. No hay turnos secuenciales ni desbloqueos automáticos; la cuadrícula de
escritorio y la lista vertical móvil siguen el mismo orden. Un extremo no se mueve.

## Correo

Solo una transición efectiva de 0 a 1 envía `ApprovalUnlockedMail` al aprobador,
utilizando la plantilla y la copia de monitoreo existentes. Incluye el contrato,
la persona que habilita y el enlace a la versión vigente. Bloquear o repetir un
estado abierto no envía correo; volver a bloquear/desbloquear genera otro aviso.
El correo inicial de asignación explica que la participación comienza bloqueada.

El correo se envía después de confirmar la transacción. Si el transporte falla o
no confirma aceptación, se conserva el candado abierto y se muestra una advertencia;
el log solo registra IDs técnicos y tipo de excepción. Repetir el envío de apertura
no vuelve a notificar. Para un nuevo aviso, bloquear y desbloquear otra vez, después
de revisar el resultado. Aceptación SMTP no equivale a entrega en la bandeja final.
No se guardan historiales de estos cambios.

## Activación

Los cambios se preparan y prueban sin editar `.env` ni migrar la base habitual.
En desarrollo, usando el PHP/contenedor del proyecto:

```sh
php artisan migrate
npm run build
php artisan optimize:clear
```

En producción, respaldar la base y aplicar esta actualización durante mantenimiento.
Actualizar el código, ejecutar `php artisan migrate --force`, compilar los assets y
limpiar cachés antes de levantar el servicio. Vite 7 requiere una versión compatible
de Node; se verificó la compilación con Node 22.21.1. No ejecutar seeders ni comandos
de reinicio de base de datos. La migración pendiente debe aplicarse antes de abrir
las páginas del contrato, ya que utilizan las columnas nuevas.

## Presentación y seguimiento

Todas las tarjetas muestran un candado informativo abierto/cerrado junto al estado,
independientemente del usuario que consulte. Los botones de administración siguen
limitados al asesor asignado y abogados. La leyenda se muestra una vez, al pie de
Flujo de aprobación. Los cambios de candado y orden regresan a
`contracts/{id}#approval-flow`, también cuando la apertura se guardó pero el correo
no pudo confirmarse.

El dashboard incluye Aprobadores habilitados / Pendientes de acción. Se cargan los
aprobadores habilitados, con estado pendiente y sin fecha de aprobación, únicamente
de la versión vigente. Solo se muestran como pendientes en contratos en aprobación
u observados, con versión no aprobada y sin evidencia de firma. La columna respeta
el orden visual y cada nombre enlaza al flujo. Las comprobaciones de firma se hacen
por lote para evitar consultas por cada contrato. La visibilidad de contratos y los
filtros de roles existentes se conservan. No se agregan columnas de base de datos.

## Tiempo habilitado acumulado

La migración `2026_11_25_000000_add_enabled_time_to_approvals.php` agrega únicamente:

- `unlocked_at`: DATETIME nullable; inicio del tramo habilitado actual.
- `enabled_seconds`: BIGINT sin signo, DEFAULT 0; segundos de tramos ya cerrados.

No se crea un historial de candados ni se requiere cron. La tarjeta reemplaza
Pendiente durante por Tiempo habilitado. El cálculo se realiza al consultar:
segundos acumulados más el tramo abierto, sin modificar datos por una lectura.
La duración se presenta con hasta dos unidades entre días, horas y minutos, en
tiempo corrido (no días laborables), y conserva Asignado el como dato separado.

Abrir inicia un tramo; cerrar suma los segundos transcurridos y deja `unlocked_at`
en NULL; aprobar acumula hasta la fecha de aprobación y detiene el contador.
Solicitudes repetidas del mismo estado no reinician ni duplican tiempo. Los cambios
son parte de las transacciones/bloqueos ya usados para candados y aprobación. El
resultado del correo de habilitación no altera el contador: mide disponibilidad
de la opción, no lectura del correo ni actividad real dentro del documento.

Una nueva versión formal detiene los tramos de la anterior y crea asignaciones con
candado cerrado y cero segundos. Una revisión del documento dentro de la misma versión
no reinicia tiempo. Revertir excepcionalmente una aprobación conserva el acumulado y,
si sigue habilitada, inicia otro tramo en ese momento: el período aprobado no suma.
Los recordatorios utilizan el mismo total y requieren que el aprobador esté habilitado.

Como no existe registro histórico de cambios de candado, la migración no atribuye
actividad pasada: deja todos los acumulados en cero e inicia el tramo en su fecha de
ejecución solo para aprobaciones pendientes habilitadas de la versión vigente, en
contratos abiertos y sin evidencia de firma. Versiones históricas, aprobaciones
registradas y candados cerrados quedan sin tramo abierto. No modifica candados,
posiciones, fechas de asignación, decisiones ni documentos. La tarjeta incluye una
ayuda indicando que la medición no incluye períodos anteriores a su activación.

Aplicar la migración antes de abrir los contratos, usando el PHP de la aplicación:

```sh
php artisan migrate
php artisan optimize:clear
```

En producción, respaldo y mantenimiento como en la actualización de candados; usar
`migrate --force`. No se ejecutaron migraciones sobre la base habitual al desarrollar:
las verificaciones usan SQLite en memoria y correo de pruebas, sin SMTP real.
