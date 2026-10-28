<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Mod Luar Talian — eHadir</title>

    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    @include('partials.pwa-head')
    @vite(['resources/css/app.css'])

    <style>
        /* Fallback offline inline styles in case Vite CSS bundle is not yet cached */
        :root {
            --bg-color: #f8fafc;
            --text-color: #1e293b;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --accent: #059669;
        }
        html.dark {
            --bg-color: #020617;
            --text-color: #f1f5f9;
            --card-bg: #0f172a;
            --border-color: #1e293b;
            --accent: #10b981;
        }
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen flex flex-col items-center justify-center p-4">

    <div class="max-w-md w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl text-center">
        <!-- Brand Header -->
        <div class="flex items-center justify-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-extrabold text-2xl shadow-md">
                eH
            </div>
            <div class="text-left">
                <h1 class="text-xl font-bold text-slate-900 dark:text-white leading-tight">eHadir</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Sistem Kehadiran Organisasi</p>
            </div>
        </div>

        <!-- Offline Status Graphic -->
        <div class="mx-auto w-16 h-16 rounded-full bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 flex items-center justify-center text-amber-600 dark:text-amber-400 mb-5">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 4.243a9 9 0 01-2.828-6.364 9 9 0 012.828-6.364M3 3l18 18M9.879 9.879a3 3 0 004.242 4.242" />
            </svg>
        </div>

        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">
            Tiada Sambungan Internet
        </h2>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-6 leading-relaxed">
            Peranti anda sedang berada di luar talian (offline). Sebahagian fungsi utama memerlukan rangkaian internet untuk beroperasi dengan selamat.
        </p>

        <!-- Information Card -->
        <div class="bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 rounded-xl p-4 text-left text-xs text-slate-600 dark:text-slate-300 space-y-2 mb-6">
            <p class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Perhatian:
            </p>
            <ul class="list-disc list-inside space-y-1 text-slate-500 dark:text-slate-400">
                <li>Perekodan status & kehadiran tidak dapat dihantar tanpa talian.</li>
                <li>Data kehadiran akan disegerakkan sebaik sambungan pulih.</li>
                <li>Sistem akan menyambung semula secara automatik.</li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-3">
            <button
                type="button"
                id="btn-retry"
                onclick="retryConnection()"
                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-medium text-sm rounded-xl transition duration-150 shadow-sm cursor-pointer"
            >
                <svg id="retry-icon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span id="retry-text">Cuba Semula Sekarang</span>
            </button>

            <p id="status-hint" class="text-[11px] text-slate-400 dark:text-slate-500 italic">
                Menunggu isyarat rangkaian...
            </p>
        </div>
    </div>

    <script>
        function retryConnection() {
            const btn = document.getElementById('btn-retry');
            const icon = document.getElementById('retry-icon');
            const text = document.getElementById('retry-text');
            const hint = document.getElementById('status-hint');

            if (icon) icon.classList.add('animate-spin');
            if (text) text.textContent = 'Memeriksa sambungan...';
            if (hint) hint.textContent = 'Menghubungi pelayan eHadir...';

            fetch('/manifest.webmanifest', { method: 'HEAD', cache: 'no-store' })
                .then(() => {
                    if (hint) hint.textContent = 'Sambungan ditemui! Memuatkan semula...';
                    window.location.reload();
                })
                .catch(() => {
                    setTimeout(() => {
                        if (icon) icon.classList.remove('animate-spin');
                        if (text) text.textContent = 'Cuba Semula Sekarang';
                        if (hint) hint.textContent = 'Pelayan masih tidak dapat dihubungi.';
                    }, 1000);
                });
        }

        // Automatic retry when browser signals online
        window.addEventListener('online', () => {
            const hint = document.getElementById('status-hint');
            if (hint) hint.textContent = 'Talian kembali! Memuat semula...';
            setTimeout(() => {
                window.location.reload();
            }, 600);
        });
    </script>
</body>
</html>

