@props([
    'anggota',
    'bahagianList',
    'unitList',
    'currentPlacement' => null,
])

<div class="space-y-4" x-data="{ type: '{{ old('penempatan_type', 'bahagian') }}', autoTransfer: true }">
    @if ($currentPlacement)
        <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs">
            <p class="font-semibold text-emerald-800 dark:text-emerald-300">Penempatan Semasa Aktif:</p>
            <p class="text-emerald-700 dark:text-emerald-400 mt-0.5">
                {{ $currentPlacement->jenis_penempatan }}: <span class="font-bold">{{ $currentPlacement->lokasi_penempatan }}</span> (Sejak {{ $currentPlacement->tarikh_mula->format('d/m/Y') }})
            </p>
        </div>
    @endif

    <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Pilih Entiti Destinasi <span class="text-rose-500">*</span>
        </label>
        <div class="flex items-center gap-6 py-1">
            <label class="inline-flex items-center text-sm cursor-pointer">
                <input type="radio" name="penempatan_type" value="bahagian" x-model="type" class="text-emerald-600 focus:ring-emerald-500">
                <span class="ml-2 font-medium text-slate-800 dark:text-slate-200">Bahagian</span>
            </label>
            <label class="inline-flex items-center text-sm cursor-pointer">
                <input type="radio" name="penempatan_type" value="unit" x-model="type" class="text-emerald-600 focus:ring-emerald-500">
                <span class="ml-2 font-medium text-slate-800 dark:text-slate-200">Unit</span>
            </label>
        </div>
    </div>

    <div x-show="type === 'bahagian'">
        <x-form.select name="bahagian_id" label="Pilih Bahagian">
            <option value="">-- Sila Pilih Bahagian --</option>
            @foreach ($bahagianList as $b)
                <option value="{{ $b->id }}" {{ old('bahagian_id') == $b->id ? 'selected' : '' }}>
                    {{ $b->kod }} — {{ $b->nama }}
                </option>
            @endforeach
        </x-form.select>
    </div>

    <div x-show="type === 'unit'" x-cloak>
        <x-form.select name="unit_id" label="Pilih Unit">
            <option value="">-- Sila Pilih Unit --</option>
            @foreach ($unitList as $u)
                <option value="{{ $u->id }}" {{ old('unit_id') == $u->id ? 'selected' : '' }}>
                    {{ $u->kod }} — {{ $u->nama }}
                </option>
            @endforeach
        </x-form.select>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-form.input
            name="tarikh_mula"
            type="date"
            label="Tarikh Mula Berkuat Kuasa"
            :value="old('tarikh_mula', date('Y-m-d'))"
            required
        />

        <x-form.input
            name="tarikh_tamat"
            type="date"
            label="Tarikh Tamat (Jika Ada)"
            :value="old('tarikh_tamat')"
            hint="Kosongkan jika terbuka berterusan."
        />
    </div>

    @if ($currentPlacement && $currentPlacement->tarikh_tamat === null)
        <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700">
            <label class="flex items-start gap-2.5 cursor-pointer">
                <input type="checkbox" name="auto_transfer" value="1" x-model="autoTransfer" class="mt-0.5 rounded border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500">
                <span class="text-xs text-slate-700 dark:text-slate-300">
                    <strong>Tamatkan Penempatan Semasa Secara Automatik</strong><br>
                    Sistem akan menetapkan tarikh tamat penempatan lama sehari sebelum tarikh mula penempatan baharu bagi mengelakkan pertindihan tarikh.
                </span>
            </label>
        </div>
    @endif
</div>
