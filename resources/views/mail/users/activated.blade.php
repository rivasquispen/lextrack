<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6fb; margin: 0; padding: 0; }
        .wrapper { width: 100%; background-color: #f4f6fb; padding: 30px 0; }
        .email-card { max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; box-shadow: 0 10px 30px rgba(4, 57, 101, 0.15); overflow: hidden; }
        .header { background: #043965; padding: 32px; color: #ffffff; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; letter-spacing: 0.03em; }
        .content { padding: 32px; color: #1f2933; line-height: 1.6; }
        .content p { margin-bottom: 16px; }
        .button { display: inline-block; padding: 12px 28px; border-radius: 999px; background-color: #043965; color: #ffffff; text-decoration: none; font-weight: 600; letter-spacing: 0.03em; }
        .footer { padding: 20px 32px 32px; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="email-card">
            <div class="header">
                <h1>¡Bienvenido nuevamente a Lextrack!</h1>
            </div>
            <div class="content">
                <p>Hola {{ $user->nombre ?? $user->name ?? 'equipo' }},</p>
                <p>Tu cuenta ha sido activada por el equipo de cumplimiento. Ya puedes ingresar a la plataforma y gestionar tus flujos, documentos y firmas digitales.</p>
                <p>Accede usando el botón de Microsoft como siempre:</p>
                <p style="text-align: center; margin: 32px 0;">
                    <a href="{{ route('landing') }}" class="button">Entrar a Lextrack</a>
                </p>
                <p>Si no solicitaste este acceso o tienes inconvenientes, comunícate con nosotros respondiendo este correo.</p>
                <p>– Equipo Lextrack</p>
            </div>
            <div class="footer">
                Corporación Medifarma © {{ now()->year }} · Confidencial
            </div>
        </div>
    </div>
</body>
</html>
