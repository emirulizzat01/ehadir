<x-layouts.tv title="Pusat Pemantauan Operasi & Kehadiran TV — eHadir">
    <livewire:tv.tv-dashboard-view
        :date="$date ?? null"
        :mode="$mode ?? 'executive'"
        :bahagianId="$bahagianId ?? null"
        :isKiosk="$isKiosk ?? false"
    />
</x-layouts.tv>
