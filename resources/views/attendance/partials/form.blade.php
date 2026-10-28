@props([
    'attendance' => null,
    'statuses',
    'anggotaList' => null,
    'selectedAnggotaId' => null,
    'selectedDate' => null,
])

<div class="space-y-4" x-data="{ fullDay: {{ old('sepanjang_hari', $attendance ? $attendance->sepanjang_hari : 1) ? 'true' : 'false' }} }">
    @if ($anggotaList || !$attendance)
        <x-form.searchable-select
            name="anggota_id"
            label="Pilih Anggota"
            :endpoint="route('lookup.anggota')"
            :value="$selectedAnggotaId ?? $attendance?->anggota_id"
            placeholder="Cari mengikut nama, no. pekerja, atau jawatan..."
            hint="Taip sekurang-kurangnya satu aksara untuk mencari anggota dalam skop kuasa pentadbiran anda."
            required
        />
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @if (!$attendance)
            <x-form.input
                name="tarikh"
                type="date"
                label="Tarikh Kehadiran"
                :value="old('tarikh', $selectedDate ?? date('Y-m-d'))"
                required
            />
        @endif

        <div class="{{ $attendance ? 'sm:col-span-2' : '' }}">
            <x-form.select name="status_id" label="Status Kehadiran" required>
                @foreach ($statuses as $st)
                    <option value="{{ $st->id }}" {{ old('status_id', $attendance?->status_id) == $st->id ? 'selected' : '' }}>
                        {{ $st->nama }}
                    </option>
                @endforeach
            </x-form.select>
        </div>
    </div>

    <!-- Sepanjang hari toggle -->
    <div class="pt-1">
        <label class="flex items-center gap-2.5 cursor-pointer">
            <input type="checkbox" name="sepanjang_hari" value="1" x-model="fullDay" class="rounded border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500">
            <span class="text-sm font-medium text-slate-800 dark:text-slate-200">
                Kehadiran / Cuti Sepanjang Hari
            </span>
        </label>
    </div>

    <!-- Masa Mula & Tamat -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="!fullDay" x-cloak>
        <x-form.input
            name="masa_mula"
            type="time"
            label="Masa Mula"
            hint="Format 24 jam"
            :value="old('masa_mula', $attendance?->masa_mula)"
        />

        <x-form.input
            name="masa_tamat"
            type="time"
            label="Masa Tamat"
            hint="Mesti selepas masa mula"
            :value="old('masa_tamat', $attendance?->masa_tamat)"
        />
    </div>

    <x-form.input
        name="lokasi"
        label="Lokasi / Tempat (Jika Kerja Luar)"
        placeholder="Contoh: Putrajaya / Tapak Projek Zon A"
        :value="old('lokasi', $attendance?->lokasi)"
    />

    <x-form.textarea
        name="catatan"
        label="Catatan Tambahan / No. Rujukan MC / Arahan"
        placeholder="Maklumat sokongan jika ada..."
        :value="old('catatan', $attendance?->catatan)"
        rows="3"
    />
</div>
