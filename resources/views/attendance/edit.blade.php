<x-layouts.app title="Kemaskini Rekod Kehadiran">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Kehadiran', 'url' => route('attendance.index')],
        ['label' => $attendance->tarikh->format('d/m/Y'), 'url' => route('attendance.show', $attendance)],
        ['label' => 'Kemaskini']
    ]" />

    <div class="max-w-2xl mx-auto">
        <x-ui.card :title="'Kemaskini: ' . $attendance->anggota->nama_penuh_gelaran" :description="'Tarikh: ' . $attendance->tarikh->format('d/m/Y')">
            <x-slot:actions>
                <button
                    type="button"
                    class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline cursor-pointer"
                    x-data
                    @click="$dispatch('open-confirm-modal', { name: 'delete-confirm', action: '{{ route('attendance.destroy', $attendance) }}' })"
                >
                    Padam Rekod Ini
                </button>
            </x-slot:actions>

            <form method="POST" action="{{ route('attendance.update', $attendance) }}" class="space-y-5">
                @csrf
                @method('PUT')

                @include('attendance.partials.form', [
                    'attendance' => $attendance,
                    'statuses' => $statuses,
                    'anggotaList' => null
                ])

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button href="{{ route('attendance.show', $attendance) }}" variant="secondary">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Kemaskini Rekod
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
