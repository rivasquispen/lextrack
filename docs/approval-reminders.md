# Recordatorios individuales de aprobación

En `/contracts/{id}`, el icono Lucide `mail-warning` aparece a la izquierda de
la papelera en cada aprobación pendiente. Cualquier usuario con rol abogado puede
utilizarlo; para el rol asesor, debe estar asignado al contrato. El admin no tiene un bypass.

El formulario exige confirmación antes de enviar. En backend se revalida la
rol del remitente y asignación si es asesor, pertenencia de la aprobación, versión vigente, decisión
pendiente y ausencia de evidencia de firma. Solo se permiten contratos
`en_aprobacion` u `observado`, coherente con el endpoint normal de aprobación.

## Correo y días pendientes

El destinatario es el usuario de la aprobación, no un email recibido del navegador.
Se utiliza el nombre real del abogado/asesor remitente, código, título y enlace
al contrato. No se menciona la versión en el correo.

Los días son días completos transcurridos desde `assigned_at`, o `created_at` si
falta la asignación. Para menos de 24 horas se indica “menos de un día”; se distingue
“1 día” de “N días”. No se consulta `approval_reopenings`: una reversión excepcional
no reinicia este contador.

Se utiliza el mailer ya configurado, sin modificar `.env`. Con `MAIL_MAILER=log`
el cuerpo se registra en logs y no sale un correo real; con SMTP usa el transporte
existente. Se conserva la copia de monitoreo configurada para los correos del sistema.
El mensaje de éxito significa aceptación por el transporte, no entrega en bandeja.

## Auditoría y frecuencia

La nueva tabla `approval_reminders` guarda contrato, versión, aprobación, remitente,
destinatario, días, snapshot, estado y fechas. No hay cambios de columnas existentes
ni modificaciones a decisiones, documentos, versiones o estados del contrato.

Estados: `sending` (reservado/procesando), `sent` (aceptado por el transporte),
`failed` (envío no confirmado). Si el correo fue aceptado pero falla guardar el
resultado, queda `sending` y se registra un error con el ID del recordatorio.

Se reserva el intento en una transacción con bloqueo del contrato y aprobación
antes del envío, compartiendo orden de bloqueo con aprobación/reversión.
La llamada de correo ocurre después de esa transacción, sin mantener bloqueos
durante SMTP. La decisión podría cambiar mientras el correo está en tránsito;
el correo explica que puede ignorarse si el usuario ya actuó.

El límite es de un intento cada 24 horas por aprobación, independientemente del
remitente. Incluye intentos fallidos o de resultado incierto para no duplicar
correos ante timeouts; no se reintentan automáticamente. Una nueva aprobación
asignada en otra versión tiene su propio límite. En la tarjeta se muestra el
último resultado y se deshabilita temporalmente el botón.

Los logs de resultado contienen ID de recordatorio/contrato y tipo de excepción,
sin imprimir credenciales ni respuestas SMTP completas. Para ver los eventos:

```bash
tail -f storage/logs/laravel.log
```

Si se usa otro canal de logging, revisar el archivo configurado para ese canal.
No probar con SMTP real si solo se desea verificar el contenido en el log.

## Despliegue

Nuevos archivos:

- app/Http/Controllers/ApprovalReminderController.php
- app/Services/ApprovalReminderService.php
- app/Models/ApprovalReminder.php
- app/Mail/ApprovalReminderMail.php
- resources/views/mail/contracts/approval-reminder.blade.php
- database/migrations/2026_10_21_000000_create_approval_reminders_table.php

Modificados: routes/web.php, app/Http/Controllers/ContractController.php y
resources/views/contracts/show.blade.php. Las pruebas nuevas están en
tests/Feature/ApprovalReminderTest.php; ambas suites de aprobaciones comparten
tests/Support/UsesContractTestDatabase.php para el aislamiento de migraciones.

1. Respaldar base/código y validar en staging con el motor de BD productivo.
2. Durante mantenimiento, actualizar el código y ejecutar solo la migración nueva:

   ```bash
   php artisan migrate --path=database/migrations/2026_10_21_000000_create_approval_reminders_table.php --force
   ```

3. Generar y desplegar el build completo (`npm run build`), limpiar cachés de rutas
   y vistas, y habilitar la aplicación. No cambian dependencias ni `.env`.
4. Verificar visibilidad con abogados asignados/no asignados, asesores asignados/no asignados y estado pendiente.
   Pulsar el botón sí procesa un correo con el transporte activo.

La migración está preparada; las pruebas no la ejecutan sobre la BD del `.env`.
No borrar la tabla al retirar la funcionalidad si ya contiene auditoría.

## Pruebas

```bash
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: MAIL_MAILER=array \
  php vendor/bin/phpunit tests/Feature/ApprovalReminderTest.php tests/Feature/ApprovalReopeningTest.php
```

Se utiliza transporte de correo en memoria, sin SMTP, y base efímera. Incluye
permisos, bloqueo entre remitentes, fechas reales, reversiones excepcionales,
errores de transporte/persistencia y visibilidad de tarjetas. No simula contención
real entre conexiones SQL Server: verificar ese escenario en staging.
