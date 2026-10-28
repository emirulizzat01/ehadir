<x-layouts.guest title="Log Masuk">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">
            Log Masuk Sistem
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Sila masukkan emel dan kata laluan untuk mengakses eHadir.
        </p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <x-form.input
            name="email"
            label="Alamat Emel"
            type="email"
            placeholder="nama@ehadir.gov.my"
            required
            autofocus
        />

        <x-form.input
            name="password"
            label="Kata Laluan"
            type="password"
            placeholder="••••••••"
            required
        />

        <div class="flex items-center justify-between pt-1">
            <label class="inline-flex items-center text-xs text-slate-600 dark:text-slate-400 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded-sm border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 dark:bg-slate-800">
                <span class="ml-2">Ingat saya</span>
            </label>
        </div>

        <div class="pt-2">
            <x-ui.button type="submit" variant="primary" class="w-full">
                Log Masuk ke Sistem
            </x-ui.button>
        </div>
    </form>

    <!-- Demo credential helper box -->
    <div class="mt-6 p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700/60 text-xs text-slate-600 dark:text-slate-400">
        <p class="font-semibold text-slate-800 dark:text-slate-200 mb-1">Akaun Pentadbir Lalai (Seeded):</p>
        <div class="space-y-0.5 font-mono text-[11px]">
            <p>Emel: <span class="text-emerald-600 dark:text-emerald-400">izzat@kejora.gov.my</span></p>
            <p>Kata Laluan: <span class="text-emerald-600 dark:text-emerald-400">Password@123</span></p>
        </div>
    </div>
</x-layouts.guest>
