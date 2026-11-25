<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Recordatorio de aprobación</title></head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6fb; margin: 0; padding: 30px 16px; color: #334155;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden;">
        <div style="background: #043965; color: #ffffff; padding: 28px; text-align: center;">
            <h1 style="font-size: 24px; margin: 0;">Tienes un contrato pendiente</h1>
            <p>{{ $snapshot['contract_code'] }}</p>
        </div>
        <div style="padding: 28px; line-height: 1.6;">
            <p>Hola, {{ $snapshot['recipient_name'] }}:</p>
            <p><strong>{{ $snapshot['sender_name'] }}</strong> te recuerda que el contrato
                <strong>{{ $snapshot['contract_code'] }} — {{ $snapshot['contract_title'] }}</strong>
                @if (array_key_exists('enabled_seconds', $snapshot))
                    está pendiente de tu aprobación. Tu participación acumula <strong>{{ $elapsed }}</strong> de tiempo habilitado.
                @else
                    está pendiente de tu aprobación desde hace <strong>{{ $elapsed }}</strong>.
                @endif
            </p>
            <p>Ingresa a Lextrack para revisar el documento y registrar tu decisión.</p>
            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ $ctaUrl }}" style="display: inline-block; padding: 12px 24px; background: #043965; border-radius: 8px; color: #ffffff; text-decoration: none;">Revisar contrato</a>
            </p>
            <p style="font-size: 12px; color: #64748b;">Si ya registraste tu decisión al recibir este correo, puedes ignorar el recordatorio.</p>
        </div>
    </div>
</body>
</html>
