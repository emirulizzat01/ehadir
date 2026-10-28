<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Pusat Pemantauan Operasi & Kehadiran TV — eHadir' }}</title>

    @include('partials.pwa-head')

    <!-- Anti-Flash Theme Script (Defaults to dark for TV Command Center, respects saved theme) -->
    <script>
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>

    <!-- Global Theme & Fullscreen Helper Functions (SOP Priority 8 & Kiosk Controls) -->
    <script>
        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                window.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark: false } }));
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                window.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark: true } }));
            }
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.error('Error attempting to enable fullscreen:', err);
                });
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body
    x-data="{
        isFullscreen: false,
        isOnline: navigator.onLine,
        isDark: document.documentElement.classList.contains('dark'),
        init() {
            // Keep TV screen awake using Screen Wake Lock API
            if ('wakeLock' in navigator) {
                try {
                    navigator.wakeLock.request('screen');
                } catch (err) {
                    console.log('Wake Lock error:', err);
                }
            }

            // Monitor online / offline status
            window.addEventListener('online', () => { this.isOnline = true; });
            window.addEventListener('offline', () => { this.isOnline = false; });

            document.addEventListener('fullscreenchange', () => {
                this.isFullscreen = !!document.fullscreenElement;
            });

            window.addEventListener('theme-changed', (e) => {
                this.isDark = e.detail.isDark;
            });
        },
        toggleFullscreen() {
            toggleFullscreen();
        },
        toggleTheme() {
            toggleTheme();
        }
    }"
    class="bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen h-screen w-screen overflow-hidden font-sans antialiased select-none flex flex-col justify-between transition-colors duration-200"
>
    <!-- Offline Reconnecting Badge -->
    <div
        x-show="!isOnline"
        x-cloak
        class="fixed top-3 right-3 z-50 px-3 py-1.5 rounded-xl bg-amber-500/90 text-slate-950 font-bold text-xs flex items-center gap-2 shadow-lg animate-pulse"
    >
        <span class="w-2 h-2 rounded-full bg-slate-950"></span>
        <span>Menyambung semula rangkaian...</span>
    </div>

    {{ $slot }}

    @livewireScripts
</body>
</html>
