@props([
    'anggota' => null,
    'bahagianList' => collect(),
    'unitList' => collect(),
    'includePlacement' => true,
])

<div class="space-y-6">
    <!-- Section 1: Profil Anggota -->
    <div>
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-3">
            1. Butiran Peribadi & Jawatan
        </h4>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-form.input
                name="nama_penuh"
                label="Nama Penuh"
                placeholder="Contoh: Muhammad Ali Bin Kassim"
                :value="old('nama_penuh', $anggota?->nama_penuh)"
                required
            />

            <x-form.input
                name="no_pekerja"
                label="No. Pekerja / No. Kad Pengenalan"
                placeholder="Contoh: STF-2024-001"
                hint="Pengecam unik anggota."
                :value="old('no_pekerja', $anggota?->no_pekerja)"
            />

            <x-form.input
                name="gelaran"
                label="Gelaran / Panggilan Kehormat"
                placeholder="Contoh: En. / Pn. / Dr. / Ir."
                :value="old('gelaran', $anggota?->gelaran)"
            />

            <x-form.input
                name="jawatan"
                label="Gelaran Jawatan"
                placeholder="Contoh: Pegawai Teknologi Maklumat F41"
                :value="old('jawatan', $anggota?->jawatan)"
            />

            <x-form.input
                name="gred"
                label="Gred Perkhidmatan"
                placeholder="Contoh: FA5 / N19 / 41"
                :value="old('gred', $anggota?->gred)"
            />

            <x-form.input
                name="emel"
                type="email"
                label="Alamat Emel Rasmi"
                placeholder="Contoh: nama@kejora.gov.my"
                :value="old('emel', $anggota?->emel)"
            />

            <x-form.input
                name="no_telefon"
                label="No. Telefon"
                placeholder="Contoh: 0191234567"
                :value="old('no_telefon', $anggota?->no_telefon)"
            />

            <x-form.input
                name="susunan"
                type="number"
                label="Keutamaan Susunan Paparan"
                :value="old('susunan', $anggota?->susunan ?? 0)"
                hint="Penalaan susunan antara pegawai dalam gred yang sama (nilai lebih kecil dipaparkan dahulu)."
            />

            <div class="pt-6">
                <x-form.toggle
                    name="aktif"
                    label="Status Anggota Aktif"
                    :checked="old('aktif', $anggota ? $anggota->aktif : true)"
                />
            </div>
        </div>
    </div>

    <!-- Section 2: Penempatan Awal (Only on creation if requested) -->
    @if ($includePlacement && ! $anggota)
        <div class="pt-4 border-t border-slate-200 dark:border-slate-800" x-data="{ type: '{{ old('penempatan_type', 'bahagian') }}' }">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                2. Penempatan Awal (Pilihan)
            </h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                Anggota boleh ditempatkan secara bebas ke sebuah Bahagian atau sebuah Unit.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                        Jenis Entiti Penempatan
                    </label>
                    <div class="flex items-center gap-4 py-2">
                        <label class="inline-flex items-center text-sm cursor-pointer">
                            <input type="radio" name="penempatan_type" value="bahagian" x-model="type" class="text-emerald-600 focus:ring-emerald-500">
                            <span class="ml-2 font-medium">Bahagian</span>
                        </label>
                        <label class="inline-flex items-center text-sm cursor-pointer">
                            <input type="radio" name="penempatan_type" value="unit" x-model="type" class="text-emerald-600 focus:ring-emerald-500">
                            <span class="ml-2 font-medium">Unit</span>
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

                <div>
                    <x-form.input
                        name="tarikh_mula"
                        type="date"
                        label="Tarikh Mula Penempatan"
                        :value="old('tarikh_mula', date('Y-m-d'))"
                    />
                </div>
            </div>
        </div>
    @endif
</div>
