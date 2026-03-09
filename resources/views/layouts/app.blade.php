

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @include('partials.favicons')

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>


    <body class="font-sans antialiased bg-muted min-h-screen">

        <div class="min-h-screen flex flex-col">
            <header class="bg-white shadow-sm z-40">
                <div class="max-w-7xl- mx-auto px-6 py-4 flex flex-wrap items-center gap-6">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="Lextrack" class="h-10 w-10-  p-2">
                    </div>

                    <nav class="flex-1">
                        <ul class="flex flex-wrap gap-3">
                            @foreach ($menu as $item)
                                <li>
                                    <a href="{{ $item['route'] }}" class="px-4 py-2 rounded-xl text-sm font-medium transition {{ $item['active'] ? 'bg-primary text-white' : 'text-slate-500 hover:text-primary hover:bg-primary/5' }}">
                                        {{ $item['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>

                    @auth
                        @php
                            $notificationToneClasses = [
                                'primary' => 'border border-primary/40 bg-primary/5',
                                'amber' => 'border border-amber-200 bg-amber-50/80',
                                'neutral' => 'border border-slate-200 bg-slate-50/80',
                                'default' => 'border border-slate-100 bg-white',
                            ];
                        @endphp
                        <div class="flex items-center gap-4">
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" @click.outside="open = false" class="relative rounded-full bg-primary/5 p-3 text-primary hover:bg-primary/10 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.657a2 2 0 001.414 3.414H7.728a2 2 0 001.414-3.414A8 8 0 113.1 9.75c0 1.593-.815 3.115-1.792 4.328a1 1 0 00.8 1.622h19.784a1 1 0 00.8-1.623 8 8 0 01-1.792-4.327 8 8 0 00-5.043-7.37" />
                                    </svg>
                                    @if ($notificationCount > 0)
                                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[1.5rem] px-1 h-5 flex items-center justify-center">{{ $notificationCount }}</span>
                                    @endif
                                </button>
                                <div x-cloak x-show="open" x-transition class="absolute right-0 mt-3 w-96 bg-white rounded-2xl shadow-panel p-4 space-y-4" style="max-height: 450px; overflow-y: auto;">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-semibold text-slate-600">Notificaciones</p>
                                        <span class="text-xs text-slate-400">{{ $notificationCount }} pendientes</span>
                                    </div>
                                    <div class="space-y-4">
                                        @if ($notificationGroups->isNotEmpty())
                                            @foreach ($notificationGroups as $group)
                                                <div>
                                                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400 mb-2">{{ $group['title'] }}</p>
                                                    <ul class="space-y-2">
                                                        @foreach ($group['items'] as $item)
                                                            @php
                                                                $toneKey = $item['tone'] ?? 'default';
                                                                $toneClass = $notificationToneClasses[$toneKey] ?? $notificationToneClasses['default'];
                                                            @endphp
                                                            <li>
                                                                <a href="{{ $item['url'] }}" class="rounded-xl p-3 block hover:border-primary hover:bg-primary/5 transition {{ $toneClass }}">
                                                                    <p class="text-sm font-semibold text-slate-800">{{ $item['title'] }}</p>
                                                                    <p class="text-xs text-slate-500">{{ $item['description'] }}</p>
                                                                    @if (! empty($item['meta']))
                                                                        <span class="text-xs text-slate-400 mt-1 block">{{ $item['meta'] }}</span>
                                                                    @endif
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endforeach
                                        @else
                                            <p class="text-sm text-slate-500 text-center">No tienes notificaciones pendientes.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" @click.outside="open = false" class="flex items-center gap-3 bg-primary/5 rounded-full ps-2 pe-4 py-1.5 text-sm font-medium text-primary">
                                    <span class="inline-flex items-center justify-center rounded-full bg-primary text-white w-10 h-10 text-base font-semibold">{{ $userInitials }}</span>
                                    <span class="text-slate-700">{{ $userName }}</span>
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                                    </svg>
                                </button>
                                <div x-cloak x-show="open" x-transition class="absolute right-0 mt-3 w-56 bg-white rounded-2xl shadow-panel py-3">
                                    @can('view-admin-dashboard')
                                        <a href="{{ route('admin.dashboard') }}" class="block px-5 py-2 text-sm text-primary font-semibold hover:bg-primary/5">Backend</a>
                                    @endcan
                                    <a href="{{ route('profile.edit') }}" class="block px-5 py-2 text-sm text-slate-600 hover:bg-primary/5">Perfil</a>
                                    <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 mt-2">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-5 py-2 text-sm text-red-600 hover:bg-red-50">Cerrar sesión</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endauth
                </div>
            </header>

            <main class="flex-1">
                @if (request()->routeIs('admin.*'))
                    <x-admin-layout>
                        @isset($header)
                            <x-slot name="header">
                                {{ $header }}
                            </x-slot>
                        @endisset

                        {{ $slot }}
                    </x-admin-layout>
                @else
                    @isset($header)
                        <div class="bg-white shadow-sm border-t">
                            <div class="max-w-7xl- mx-auto px-4 py-5 sm:px-6 lg:px-8">
                                {{ $header }}
                            </div>
                        </div>
                    @endisset

                    {{ $slot }}
                @endif
            </main>

            <footer class="text-center text-sm text-slate-500 py-6">
                humanova® Lab Corporation
            </footer>
        </div>

        @stack('modals')

        @stack('scripts')
    </body>
</html>
