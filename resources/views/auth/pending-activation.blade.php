<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Activación pendiente &middot; {{ config('app.name', 'Lextrack') }}</title>
    @include('partials.favicons')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-muted flex items-center justify-center p-6">
    <div class="max-w-xl w-full glass-panel rounded-3xl p-8 text-center space-y-4 shadow-2xl">
        <img src="{{ asset('assets/images/logo.png') }}" alt="Lextrack" class="h-12 mx-auto">
        <!--<p class="text-xs uppercase tracking-[0.4em] text-primary">Activación requerida</p>-->
        <h1 class="text-2xl font-semibold text-slate-800">Tu cuenta está pendiente de aprobación</h1>
        <p class="text-slate-600">
            Hemos recibido los datos de tu cuenta de Microsoft, pero necesitas autorización interna antes de acceder a la plataforma.
        </p>
        <p class="text-sm text-slate-500">
            Contacta a nuestro equipo por WhatsApp al
            <a class="text-primary font-semibold" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('services.support.whatsapp', '')) }}" target="_blank" rel="noopener">
                {{ config('services.support.whatsapp') }}
            </a>
            para solicitar la activación.
        </p>
        @if (session('pending_email'))
            <p class="text-xs text-slate-400">Cuenta detectada: <span class="font-medium">{{ session('pending_email') }}</span></p>
        @endif
        <a href="{{ route('landing') }}" class="btn-lex btn-lex-primary inline-flex justify-center">Volver al inicio</a>
    </div>
</body>
</html>
