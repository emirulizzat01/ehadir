@props([
    'unit' => null,
])

<div class="space-y-4">
    <x-form.input
        name="kod"
        label="Kod Unit"
        placeholder="Contoh: UINT, UUU, UKK"
        hint="Kod unik yang mewakili unit (maksimum 40 aksara)."
        :value="old('kod', $unit?->kod)"
        required
    />

    <x-form.input
        name="nama"
        label="Nama Penuh Unit"
        placeholder="Contoh: Unit Integriti"
        :value="old('nama', $unit?->nama)"
        required
    />

    <div class="pt-2">
        <x-form.toggle
            name="aktif"
            label="Status Unit Aktif"
            hint="Unit aktif boleh dipilih untuk penempatan anggota."
            :checked="old('aktif', $unit ? $unit->aktif : true)"
        />
    </div>
</div>
