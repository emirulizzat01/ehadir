<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' — eHadir' : 'eHadir — Sistem Kehadiran Organisasi' }}</title>

    <!-- Anti-Flash Theme Script (SOP Priority 8) -->
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
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen flex flex-col font-sans antialiased">

    <div class="flex flex-1 min-h-screen">
        <!-- DESKTOP & TABLET SIDE NAVIGATION (SOP Priority 7) -->
        <!-- Pinned to the viewport height so the nav and account footer never scroll. -->
        <aside class="sticky top-0 hidden h-dvh w-64 shrink-0 flex-col overflow-hidden border-r border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 md:flex">
            <!-- Brand -->
            <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 px-6 dark:border-slate-800">
                <div class="w-9 h-9 rounded-lg bg-emerald-600 flex items-center justify-center text-white font-bold text-lg shadow-xs">
                    eH
                </div>
                <div>
                    <h1 class="text-base font-bold text-slate-900 dark:text-white leading-tight">
                        eHadir
                    </h1>
                    <p class="text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-semibold">
                        v5.0 Organisasi
                    </p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <x-sidebar.link :href="route('dashboard')" icon="dashboard" :active="request()->routeIs('dashboard')">
                    Paparan Utama
                </x-sidebar.link>

                @if (auth()->user()?->hasPermission('attendance.view'))
                    <x-sidebar.link :href="route('attendance.index')" icon="attendance" :active="request()->routeIs('attendance.*')">
                        Kehadiran Harian
                    </x-sidebar.link>

                    <x-sidebar.link
                        :href="route('tv.index')"
                        icon="arrows-pointing-out"
                        icon-class="text-emerald-600 transition-transform group-hover:scale-110 dark:text-emerald-400"
                        target="_blank"
                    >
                        Paparan Skrin TV
                        <x-slot:trailing>
                            <span class="shrink-0 rounded bg-emerald-100 px-1.5 py-0.5 text-[9px] font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">LIVE</span>
                        </x-slot:trailing>
                    </x-sidebar.link>
                @endif

                @if (auth()->user()?->hasPermission('calendar.view'))
                    <x-sidebar.link :href="route('calendar.index')" icon="calendar" :active="request()->routeIs('calendar.*')">
                        Kalendar & Aktiviti
                    </x-sidebar.link>
                @endif

                @if (auth()->user()?->hasPermission('anggota.view'))
                    <x-sidebar.link :href="route('personnel.anggota.index')" icon="anggota" :active="request()->routeIs('personnel.*')">
                        Direktori Anggota
                    </x-sidebar.link>
                @endif

                @if (auth()->user()?->hasPermission('org.view'))
                    <x-sidebar.section>Organisasi</x-sidebar.section>

                    <x-sidebar.link :href="route('organization.bahagian.index')" icon="organization" :active="request()->routeIs('organization.bahagian.*')">
                        Bahagian
                    </x-sidebar.link>

                    <x-sidebar.link :href="route('organization.unit.index')" icon="organization" :active="request()->routeIs('organization.unit.*')">
                        Unit
                    </x-sidebar.link>

                    @if (auth()->user()?->isSuperAdmin())
                        <x-sidebar.link :href="route('organization.attendance-statuses.index')" icon="shield" :active="request()->routeIs('organization.attendance-statuses.*')">
                            Status Kehadiran
                        </x-sidebar.link>
                    @endif
                @endif

                @if (auth()->user()?->hasPermission('reports.export') || auth()->user()?->hasPermission('attendance.view'))
                    <x-sidebar.section>Laporan</x-sidebar.section>

                    <x-sidebar.link :href="route('reports.daily')" icon="reports" :active="request()->routeIs('reports.*')">
                        Laporan Kehadiran
                    </x-sidebar.link>
                @endif

                @if (auth()->user()?->isSuperAdmin())
                    <x-sidebar.section>Pentadbiran Sistem</x-sidebar.section>

                    <x-sidebar.link :href="route('users.index')" icon="users" :active="request()->routeIs('users.*')">
                        Pengguna & Skop
                    </x-sidebar.link>

                    <x-sidebar.link :href="route('audit.index')" icon="audit" :active="request()->routeIs('audit.*')">
                        Audit Trail
                    </x-sidebar.link>
                @endif
            </nav>

            <!-- User Info & Logout -->
            <div class="sidebar-footer border-t border-slate-200 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-800/40">
                <div class="mb-1.5 flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <a href="{{ route('profile.edit') }}" class="block truncate text-xs font-semibold text-slate-900 hover:text-emerald-600 dark:text-white dark:hover:text-emerald-400">
                            {{ auth()->user()?->nama }}
                        </a>
                        <p class="truncate text-[10px] text-slate-500 dark:text-slate-400">
                            {{ auth()->user()?->email }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Log Keluar" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                            <x-icon name="logout" class="w-4 h-4" />
                        </button>
                    </form>
                </div>

                <!-- Scope Indicator -->
                <div class="pt-1">
                    @if (auth()->user()?->isSuperAdmin())
                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-md">
                            <x-icon name="shield" class="w-3 h-3" />
                            Akses Superadmin Penuh
                        </span>
                    @else
                        @php
                            $scopesCount = auth()->user()?->scopes->count() ?? 0;
                        @endphp
                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-slate-700 dark:text-slate-300 bg-slate-200 dark:bg-slate-700 px-2 py-0.5 rounded-md">
                            <x-icon name="organization" class="w-3 h-3" />
                            {{ $scopesCount > 0 ? "Diberi Skop ({$scopesCount})" : "Tiada Skop" }}
                        </span>
                    @endif
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col min-w-0 pb-20 md:pb-8">
            <!-- TOP HEADER -->
            <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-4 sm:px-6 lg:px-8 sticky top-0 z-30">
                <div class="flex items-center gap-3">
                    <!-- Mobile Logo (shown only on mobile) -->
                    <div class="flex md:hidden items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center text-white font-bold text-sm">
                            eH
                        </div>
                        <span class="font-bold text-slate-900 dark:text-white text-base">eHadir</span>
                    </div>

                    @isset($header)
                        <div class="hidden sm:block">
                            {{ $header }}
                        </div>
                    @endisset
                </div>

                <!-- Right Header Controls -->
                <div class="flex items-center gap-3">
                    <!-- Timezone & Date Indicator (SOP Priority 4) -->
                    <div class="hidden lg:flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700">
                        <x-icon name="calendar" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                        <span>{{ now()->translatedFormat('l, d F Y') }}</span>
                    </div>

                    <!-- TV Display Button -->
                    @if (auth()->user()?->hasPermission('attendance.view'))
                        <a
                            href="{{ route('tv.index') }}"
                            target="_blank"
                            title="Buka Paparan Skrin TV / Kiosk Bilik Gerakan"
                            class="p-2 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 transition-colors"
                        >
                            <x-icon name="arrows-pointing-out" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                        </a>
                    @endif

                    <!-- Theme Toggler (SOP Priority 8) -->
                    <button
                        type="button"
                        onclick="toggleTheme()"
                        title="Tukar Mod Tema"
                        class="p-2 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 transition-colors cursor-pointer"
                    >
                        <span class="dark:hidden"><x-icon name="moon" class="w-4 h-4" /></span>
                        <span class="hidden dark:inline"><x-icon name="sun" class="w-4 h-4" /></span>
                    </button>
                </div>
            </header>

            <!-- MAIN BODY -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 w-full" x-data>
                <!-- Flash Alerts -->
                @if (session('success'))
                    <x-ui.alert type="success">
                        {{ session('success') }}
                    </x-ui.alert>
                @endif

                @if (session('error'))
                    <x-ui.alert type="error">
                        {{ session('error') }}
                    </x-ui.alert>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- MOBILE BOTTOM NAVIGATION (SOP Priority 7) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-1 shadow-lg">
        <div class="grid grid-cols-5 items-center justify-around">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center py-1 text-center {{ request()->routeIs('dashboard') ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-slate-500 dark:text-slate-400' }}">
                <x-icon name="dashboard" class="w-5 h-5" />
                <span class="text-[10px] mt-0.5 truncate">Utama</span>
            </a>

            @if (auth()->user()?->hasPermission('attendance.view'))
                <a href="{{ route('attendance.index') }}" class="flex flex-col items-center justify-center py-1 text-center {{ request()->routeIs('attendance.*') ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-slate-500 dark:text-slate-400' }}">
                    <x-icon name="attendance" class="w-5 h-5" />
                    <span class="text-[10px] mt-0.5 truncate">Kehadiran</span>
                </a>
            @endif

            @if (auth()->user()?->hasPermission('calendar.view'))
                <a href="{{ route('calendar.index') }}" class="flex flex-col items-center justify-center py-1 text-center {{ request()->routeIs('calendar.*') ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-slate-500 dark:text-slate-400' }}">
                    <x-icon name="calendar" class="w-5 h-5" />
                    <span class="text-[10px] mt-0.5 truncate">Kalendar</span>
                </a>
            @endif

            @if (auth()->user()?->hasPermission('anggota.view'))
                <a href="{{ route('personnel.anggota.index') }}" class="flex flex-col items-center justify-center py-1 text-center {{ request()->routeIs('personnel.*') ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-slate-500 dark:text-slate-400' }}">
                    <x-icon name="anggota" class="w-5 h-5" />
                    <span class="text-[10px] mt-0.5 truncate">Anggota</span>
                </a>
            @endif

            <a href="{{ route('reports.daily') }}" class="flex flex-col items-center justify-center py-1 text-center {{ request()->routeIs('reports.*') ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-slate-500 dark:text-slate-400' }}">
                <x-icon name="reports" class="w-5 h-5" />
                <span class="text-[10px] mt-0.5 truncate">Laporan</span>
            </a>
        </div>
    </nav>

    <!-- Global Delete Confirmation Modal (SOP Priority 9) -->
    <x-ui.confirm-modal name="delete-confirm" />

    <!-- Theme Toggle Helper Script -->
    <script>
        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }
    </script>

    @livewireScripts
</body>
</html>
