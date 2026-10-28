@props([
    'status' => null,
])

<div class="space-y-4" x-data="{ color: '{{ old('warna', $status?->warna ?? '#64748B') }}' }">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-form.input
            name="kod"
            label="Kod Status Unik"
            placeholder="Contoh: KURSUS, BERTUGAS_KHAS"
            hint="Format huruf besar tanpa ruang."
            :value="old('kod', $status?->kod)"
            required
        />

        <x-form.input
            name="nama"
            label="Nama Paparan Status"
            placeholder="Contoh: Kursus Rasmi"
            :value="old('nama', $status?->nama)"
            required
        />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
        <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Pilih Warna Lencana (HEX) <span class="text-rose-500">*</span>
            </label>
            <div class="flex items-center gap-3">
                <input
                    type="color"
                    x-model="color"
                    class="w-10 h-10 p-0.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 cursor-pointer"
                >
                <input
                    type="text"
                    name="warna"
                    x-model="color"
                    placeholder="#16A34A"
                    maxlength="7"
                    class="block w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 font-mono text-sm px-3.5 py-2 text-slate-900 dark:text-slate-100"
                    required
                >
            </div>
            @error('warna')
                <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <x-form.input
            name="susunan"
            type="number"
            label="Susunan Keutamaan"
            :value="old('susunan', $status?->susunan ?? 1)"
            hint="Nilai lebih kecil dipaparkan dahulu."
            required
        />
    </div>

    <!-- Live Preview badge -->
    <div class="p-3.5 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">Pratonton Lencana:</span>
        <x-ui.badge ::color="color">
            Contoh Status Kehadiran
        </x-ui.badge>
    </div>

    <div class="pt-2">
        <x-form.toggle
            name="aktif"
            label="Status Aktif"
            hint="Status aktif boleh dipilih dalam borang kehadiran harian."
            :checked="old('aktif', $status ? $status->aktif : true)"
        />
    </div>
</div>
