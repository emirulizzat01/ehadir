<x-layouts.app title="Catat Kehadiran Terperinci">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Kehadiran', 'url' => route('attendance.index')],
        ['label' => 'Catat Kehadiran']
    ]" />

    <div class="max-w-2xl mx-auto">
        <x-ui.card title="Catat Rekod Kehadiran Terperinci" description="Borang bagi merekod kehadiran dengan masa pelepasan, waktu bekerja luar, atau perincian cuti">
            <form method="POST" action="{{ route('attendance.store') }}" class="space-y-5">
                @csrf

                @include('attendance.partials.form', [
                    'attendance' => null,
                    'statuses' => $statuses,
                    'anggotaList' => $anggotaList,
                    'selectedAnggotaId' => $selectedAnggotaId,
                    'selectedDate' => $selectedDate
                ])

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button href="{{ route('attendance.index') }}" variant="secondary">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Simpan Rekod Kehadiran
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
