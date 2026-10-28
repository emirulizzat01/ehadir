<x-layouts.app title="Kemaskini Aktiviti">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Kalendar & Aktiviti', 'url' => route('calendar.index')],
        ['label' => $event->tajuk, 'url' => route('calendar.show', $event)],
        ['label' => 'Kemaskini']
    ]" />

    <div class="max-w-3xl mx-auto">
        <x-ui.card :title="'Kemaskini: ' . $event->tajuk" description="Ubah suai tarikh, masa, lokasi atau anggota terlibat">
            <x-slot:actions>
                <button
                    type="button"
                    class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline cursor-pointer"
                    x-data
                    @click="$dispatch('open-confirm-modal', { name: 'delete-confirm', action: '{{ route('calendar.events.destroy', $event) }}' })"
                >
                    Batalkan Program
                </button>
            </x-slot:actions>

            <form method="POST" action="{{ route('calendar.events.update', $event) }}" class="space-y-6">
                @csrf
                @method('PUT')

                @include('calendar.partials.form', [
                    'event' => $event,
                    'availableAttendees' => $availableAttendees,
                    'selectedAttendeeIds' => old('attendees', $selectedAttendeeIds),
                ])

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button href="{{ route('calendar.show', $event) }}" variant="secondary">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Kemaskini Aktiviti
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
