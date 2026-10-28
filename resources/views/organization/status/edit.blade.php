<x-layouts.app title="Kemaskini Status Kehadiran">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Organisasi'],
        ['label' => 'Status Kehadiran', 'url' => route('organization.attendance-statuses.index')],
        ['label' => $status->nama, 'url' => route('organization.attendance-statuses.edit', $status)],
        ['label' => 'Kemaskini']
    ]" />

    <div class="max-w-2xl mx-auto">
        <x-ui.card :title="'Kemaskini: ' . $status->nama" description="Ubah suai nama paparan, warna lencana dan keutamaan susunan">
            <form method="POST" action="{{ route('organization.attendance-statuses.update', $status) }}" class="space-y-5">
                @csrf
                @method('PUT')

                @include('organization.status.partials.form', ['status' => $status])

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button href="{{ route('organization.attendance-statuses.index') }}" variant="secondary">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Kemaskini Status
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
