<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' — eHadir' : 'eHadir — Log Masuk' }}</title>

    @include('partials.pwa-head')

    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen flex flex-col font-sans antialiased selection:bg-emerald-500 selection:text-white">

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 p-4">
        <!-- Brand Header -->
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-extrabold text-2xl shadow-md">
                eH
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                    eHadir
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                    Sistem Kehadiran Organisasi v5.0
                </p>
            </div>
        </div>

        <div class="w-full sm:max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 rounded-2xl shadow-sm">
            @if (session('status'))
                <x-ui.alert type="info">
                    {{ session('status') }}
                </x-ui.alert>
            @endif

            @if (session('error'))
                <x-ui.alert type="error">
                    {{ session('error') }}
                </x-ui.alert>
            @endif

            {{ $slot }}
        </div>

        <!-- Footer Notice -->
        <p class="mt-6 text-xs text-slate-400 dark:text-slate-500 text-center">
            &copy; {{ date('Y') }} Sistem Kehadiran Organisasi. Hak Cipta Terpelihara.
        </p>
    </div>

    @livewireScripts
</body>
</html>
