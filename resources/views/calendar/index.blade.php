<x-layouts.app title="Kalendar & Aktiviti Organisasi">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Kalendar & Aktiviti']
    ]" />

    @php
        $initialDate = null;
        if (request()->has('year') && request()->has('month')) {
            $initialDate = sprintf('%04d-%02d-01', (int) request('year'), (int) request('month'));
        }
    @endphp

    <livewire:calendar.calendar-view :initial-date="$initialDate" />
</x-layouts.app>
