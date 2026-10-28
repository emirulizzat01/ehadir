<x-layouts.app title="Kemaskini Penempatan">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Anggota', 'url' => route('personnel.anggota.index')],
        ['label' => $anggota->nama_penuh, 'url' => route('personnel.anggota.show', $anggota)],
        ['label' => 'Kemaskini Penempatan']
    ]" />

    <div class="max-w-2xl mx-auto">
        <x-ui.card :title="'Kemaskini Penempatan: ' . $anggota->nama_penuh_gelaran" description="Ubah suai tarikh atau bahagian/unit penempatan">
            <x-slot:actions>
                <button
                    type="button"
                    class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline cursor-pointer"
                    x-data
                    @click="$dispatch('open-confirm-modal', { name: 'delete-confirm', action: '{{ route('personnel.penempatan.destroy', [$anggota, $penempatan]) }}' })"
                >
                    Padam Rekod Penempatan
                </button>
            </x-slot:actions>

            <form method="POST" action="{{ route('personnel.penempatan.update', [$anggota, $penempatan]) }}" class="space-y-5" x-data="{ type: '{{ old('penempatan_type', $penempatan->bahagian_id ? 'bahagian' : 'unit') }}' }">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                        Pilih Entiti Destinasi
                    </label>
                    <div class="flex items-center gap-6 py-2">
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
                    <x-form.select name="bahagian_id" label="Pilih Bahagian" required>
                        <option value="">-- Sila Pilih Bahagian --</option>
                        @foreach ($bahagianList as $b)
                            <option value="{{ $b->id }}" {{ old('bahagian_id', $penempatan->bahagian_id) == $b->id ? 'selected' : '' }}>
                                {{ $b->kod }} — {{ $b->nama }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                <div x-show="type === 'unit'" x-cloak>
                    <x-form.select name="unit_id" label="Pilih Unit" required>
                        <option value="">-- Sila Pilih Unit --</option>
                        @foreach ($unitList as $u)
                            <option value="{{ $u->id }}" {{ old('unit_id', $penempatan->unit_id) == $u->id ? 'selected' : '' }}>
                                {{ $u->kod }} — {{ $u->nama }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-form.input
                        name="tarikh_mula"
                        type="date"
                        label="Tarikh Mula"
                        :value="$penempatan->tarikh_mula->format('Y-m-d')"
                        required
                    />

                    <x-form.input
                        name="tarikh_tamat"
                        type="date"
                        label="Tarikh Tamat"
                        :value="$penempatan->tarikh_tamat ? $penempatan->tarikh_tamat->format('Y-m-d') : ''"
                        hint="Biarkan kosong jika penempatan terbuka."
                    />
                </div>

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button href="{{ route('personnel.anggota.show', $anggota) }}" variant="secondary">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Kemaskini Penempatan
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
