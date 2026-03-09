<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6fb; margin: 0; padding: 0; }
        .wrapper { width: 100%; background-color: #f4f6fb; padding: 30px 0; }
        .email-card { max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; box-shadow: 0 10px 30px rgba(4, 57, 101, 0.15); overflow: hidden; }
        .header { background: #b91c1c; padding: 32px; color: #ffffff; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; letter-spacing: 0.03em; }
        .header p { margin: 10px 0 0; font-size: 14px; color: rgba(255,255,255,0.85); }
        .content { padding: 32px; color: #1f2933; line-height: 1.6; }
        .content p { margin-bottom: 16px; }
        .button { display: inline-block; padding: 12px 28px; border-radius: 999px; background-color: #b91c1c; color: #ffffff; text-decoration: none; font-weight: 600; letter-spacing: 0.03em; }
        .footer { padding: 20px 32px 32px; text-align: center; font-size: 12px; color: #94a3b8; }
        .meta { margin: 24px 0; padding: 16px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; }
        .meta p { margin: 0 0 12px; font-size: 14px; }
        .meta p:last-child { margin-bottom: 0; }
        .highlight { font-weight: 600; color: #b91c1c; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="email-card">
            <div class="header">
                <h1>Contrato observado</h1>
                <p>{{ $contract->codigo }}</p>
            </div>
            <div class="content">
                <p>Hola {{ trim($recipient->nombre ?? $recipient->name ?? '') ?: 'equipo' }},</p>
                <p>El contrato <strong>{{ $contract->titulo }}</strong> ha sido marcado como <strong>observado</strong> para generar una nueva versión y continuar con los ajustes necesarios.</p>

                <div class="meta">
                    <p><span class="highlight">Versión nueva:</span> {{ $version->numero_version }}</p>
                    <p><span class="highlight">Observado por:</span> {{ $observerName ?? 'Equipo legal' }}</p>
                    <p><span class="highlight">Estado:</span> {{ $contract->status_label ?? 'Observado' }}</p>
                    <p><span class="highlight">Categoría:</span> {{ $contract->category->nombre ?? 'Sin categoría' }}</p>
                    <p><span class="highlight">Solicitante:</span> {{ $contract->creator->nombre ?? $contract->creator->email ?? 'N/A' }}</p>
                </div>

                <p style="text-align: center; margin: 32px 0;">
                    <a href="{{ $ctaUrl ?? route('contracts.show', $contract) }}" class="button">Revisar contrato</a>
                </p>

                <p>Se han restablecido las aprobaciones para esta nueva versión. Por favor revisa los comentarios y adjuntos antes de continuar.</p>
                <p style="margin-bottom: 0;">Gracias por mantener el flujo al día.<br>— Equipo Lextrack</p>
            </div>
            <div class="footer">
                Corporación Medifarma © {{ now()->year }} · Confidencial
            </div>
        </div>
    </div>
</body>
</html>
