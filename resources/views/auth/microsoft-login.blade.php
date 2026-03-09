<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Lextrack') }} &middot; Acceso</title>
    @include('partials.favicons')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-primary text-white relative overflow-hidden">
    <div class="absolute inset-0 opacity-60" aria-hidden="true">
        <div class="w-[60vw] h-[60vw] bg-primary-light rounded-full blur-[140px] absolute -top-1/3 -left-1/4"></div>
        <div class="w-[50vw] h-[50vw] bg-accent rounded-full blur-[200px] absolute bottom-[-25%] right-[-10%]"></div>
    </div>

    <main class="relative z-10 min-h-screen flex flex-col items-center justify-center px-6 py-16">
        <div class="max-w-xl w-full space-y-10">
            @if (session('auth_error'))
                <div class="glass-panel rounded-2xl p-4 text-sm text-red-600 border border-red-100">
                    {{ session('auth_error') }}
                </div>
            @endif

            <div class="glass-panel rounded-2xl p-9 text-slate-800 shadow-panel">
                <div class="flex flex-col items-center text-center space-y-6">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Lextrack" class="h-12">

                    <p class="text-slate-600">
                        Mantén el control de documentos, aprobaciones y firmas digitales en toda la organización.
                    </p>
                    <div x-data="{ loading: false }" class="w-full">
                        <a href="{{ route('auth.microsoft.redirect') }}"
                           @click="loading = true"
                           :class="loading ? 'opacity-70 pointer-events-none' : ''"
                           class="btn-lex btn-lex-primary w-full">
                            <span class="bg-white/10 rounded-lg p-2">
                                <svg class="w-5 h-5" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M10.2564 2.125H2.12109V10.2773H10.2564V2.125Z" fill="#F35325"/>
                                    <path d="M20.8714 2.125H12.7361V10.2773H20.8714V2.125Z" fill="#81BC06"/>
                                    <path d="M10.2564 12.7227H2.12109V20.875H10.2564V12.7227Z" fill="#05A6F0"/>
                                    <path d="M20.8714 12.7227H12.7361V20.875H20.8714V12.7227Z" fill="#FFBA08"/>
                                </svg>
                            </span>
                            <span x-cloak x-show="!loading">Continuar con Microsoft</span>
                            <span x-cloak x-show="loading" class="flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                    <circle class="opacity-30" cx="12" cy="12" r="9" stroke="currentColor" />
                                    <path d="M21 12a9 9 0 00-9-9" stroke-linecap="round" />
                                </svg>
                                Conectando...
                            </span>
                        </a>
                    </div>
                    <p class="text-sm text-slate-500">
                        La autenticación está protegida con Azure AD.
                    </p>
                    <p class="text-xs text-slate-500">
                        ¿Tienes problemas para ingresar? Contacta a <a href="mailto:nrivasq@humanovalab.com" class="link-dark">nrivasq@humanovalab.com</a>.
                    </p>
                </div>
            </div>
        </div>
        <p class="mt-14 text-sm text-white/70">humanova® Lab Corporation</p>
    </main>
</body>
</html>
