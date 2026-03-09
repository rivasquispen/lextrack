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
        .header p { margin: 10px 0 0; font-size: 14px; color: rgba(255,255,255,0.85); }
        .content { padding: 32px; color: #1f2933; line-height: 1.6; }
        .content p { margin-bottom: 16px; }
        .meta { margin: 24px 0; padding: 16px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 14px; }
        .meta p { margin: 0 0 12px; }
        .meta p:last-child { margin-bottom: 0; }
        .highlight { font-weight: 600; color: #043965; }
        .button { display: inline-block; padding: 12px 28px; border-radius: 999px; background-color: #043965; color: #ffffff; text-decoration: none; font-weight: 600; letter-spacing: 0.03em; }
        .footer { padding: 20px 32px 32px; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="email-card">
            <div class="header">
                <h1>Nueva solicitud de marca</h1>
                <p>{{ $brand->display_name }}</p>
            </div>
            <div class="content">
                <p>Hola {{ trim($user->nombre ?? $user->name ?? '') ?: 'equipo' }},</p>
                <p>{{ $brand->creator->nombre ?? $brand->creator->email ?? 'Un miembro del equipo' }} registró una nueva solicitud. Estos son los detalles principales para iniciar el seguimiento.</p>

                <div class="meta">
                    <p><span class="highlight">Titular:</span> {{ $brand->display_holder }}</p>
                    <p><span class="highlight">Tipo:</span> {{ $brand->display_type }}</p>
                    <p><span class="highlight">País:</span> {{ $brand->display_country }}</p>
                    <p><span class="highlight">Estado:</span> {{ $brand->display_status }}</p>
                    <p><span class="highlight">Clases:</span> {{ $brand->classes->pluck('number')->sort()->map(fn ($number) => 'Clase '.$number)->implode(', ') ?: 'Sin clases seleccionadas' }}</p>
                    <p><span class="highlight">Registro:</span> {{ $brand->display_registration_number }}</p>
                </div>

                <p style="text-align: center; margin: 32px 0;">
                    <a href="{{ $ctaUrl ?? route('brands.index') }}" class="button">Revisar en Lextrack</a>
                </p>

                <p>Ingresa para completar la información faltante, adjuntar documentos y actualizar el estado según avanza el trámite.</p>
                <p style="margin-bottom: 0;">Gracias por mantener el portafolio de marcas al día.<br>— Equipo Lextrack</p>
            </div>
            <div class="footer">
                Corporación Medifarma © {{ now()->year }} · Confidencial
            </div>
        </div>
    </div>
</body>
</html>
