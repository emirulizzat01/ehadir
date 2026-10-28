<x-layouts.app title="Jadualkan Aktiviti">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Kalendar & Aktiviti', 'url' => route('calendar.index')],
        ['label' => 'Jadualkan Aktiviti']
    ]" />

    <div class="max-w-3xl mx-auto">
        <x-ui.card title="Jadualkan Aktiviti Baharu" description="Sila masukkan butiran program dan pilih anggota yang terlibat dalam skop anda">
            <form method="POST" action="{{ route('calendar.events.store') }}" class="space-y-6">
                @csrf

                @include('calendar.partials.form', [
                    'event' => null,
                    'availableAttendees' => $availableAttendees,
                    'selectedAttendeeIds' => old('attendees', []),
                    'defaultDate' => $defaultDate,
                    'defaultTime' => $defaultTime ?? '09:00',
                ])

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button href="{{ route('calendar.index') }}" variant="secondary">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Jadualkan Aktiviti
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
