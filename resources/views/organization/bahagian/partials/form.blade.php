@props([
    'bahagian' => null,
])

<div class="space-y-4">
    <x-form.input
        name="kod"
        label="Kod Bahagian"
        placeholder="Contoh: BTM, BPSM"
        hint="Kod unik yang mewakili bahagian (maksimum 40 aksara)."
        :value="old('kod', $bahagian?->kod)"
        required
    />

    <x-form.input
        name="nama"
        label="Nama Penuh Bahagian"
        placeholder="Contoh: Bahagian Teknologi Maklumat"
        :value="old('nama', $bahagian?->nama)"
        required
    />

    <div class="pt-2">
        <x-form.toggle
            name="aktif"
            label="Status Bahagian Aktif"
            hint="Bahagian aktif boleh dipilih untuk penempatan anggota."
            :checked="old('aktif', $bahagian ? $bahagian->aktif : true)"
        />
    </div>
</div>
