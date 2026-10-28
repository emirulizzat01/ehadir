<x-layouts.app title="Tambah Status Kehadiran">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Organisasi'],
        ['label' => 'Status Kehadiran', 'url' => route('organization.attendance-statuses.index')],
        ['label' => 'Tambah Status']
    ]" />

    <div class="max-w-2xl mx-auto">
        <x-ui.card title="Daftar Status Kehadiran Baharu" description="Sila masukkan kod, label nama dan kod warna HEX">
            <form method="POST" action="{{ route('organization.attendance-statuses.store') }}" class="space-y-5">
                @csrf

                @include('organization.status.partials.form', ['status' => null])

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button href="{{ route('organization.attendance-statuses.index') }}" variant="secondary">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Simpan Status
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
