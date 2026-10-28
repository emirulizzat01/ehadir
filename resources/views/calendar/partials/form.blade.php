@props([
    'event' => null,
    'availableAttendees',
    'selectedAttendeeIds' => [],
    'defaultDate' => null,
    'defaultTime' => '09:00',
])

@php
    $mulaVal = $event
        ? $event->mula->format('Y-m-d\TH:i')
        : old('mula', ($defaultDate ?? date('Y-m-d')) . 'T' . ($defaultTime ?? '09:00'));

    $tamatVal = $event
        ? $event->tamat->format('Y-m-d\TH:i')
        : old('tamat', \Carbon\Carbon::parse($mulaVal)->addHours(2)->format('Y-m-d\TH:i'));
@endphp

<div class="space-y-4">
    <x-form.input
        name="tajuk"
        label="Tajuk / Nama Aktiviti"
        placeholder="Contoh: Mesyuarat Penyelarasan Projek ICT Bil. 2/2026"
        :value="old('tajuk', $event?->tajuk)"
        required
    />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-form.select name="jenis" label="Kategori Aktiviti" required>
            @foreach (['MESYUARAT' => 'Mesyuarat', 'BENGKEL' => 'Bengkel', 'KURSUS' => 'Kursus', 'LAWATAN_TAPAK' => 'Lawatan Tapak', 'LAIN_LAIN' => 'Lain-lain'] as $k => $label)
                <option value="{{ $k }}" {{ old('jenis', $event?->jenis) === $k ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </x-form.select>

        <x-form.input
            name="lokasi"
            label="Lokasi / Bilik Mesyuarat"
            placeholder="Contoh: Bilik Mesyuarat Utama, Aras 4"
            :value="old('lokasi', $event?->lokasi)"
        />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-form.input
            name="mula"
            type="datetime-local"
            label="Tarikh & Masa Mula"
            :value="$mulaVal"
            required
        />

        <x-form.input
            name="tamat"
            type="datetime-local"
            label="Tarikh & Masa Tamat"
            :value="$tamatVal"
            required
        />
    </div>

    <x-form.textarea
        name="catatan"
        label="Catatan / Agenda Ringkas"
        placeholder="Agenda mesyuarat atau maklumat tambahan..."
        :value="old('catatan', $event?->catatan)"
        rows="3"
    />

    <!-- Multi-attendee selection using modern searchable attendee picker -->
    <x-form.attendee-picker
        :attendees="$availableAttendees"
        :selected="$selectedAttendeeIds"
        name="attendees"
        label="Pilih Anggota Terlibat (Skop Dibenarkan)"
        hint="Pilih seorang atau lebih anggota yang berada dalam skop pentadbiran anda:"
        required
    />
</div>
