<x-layouts.app title="Tambah Unit">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Organisasi'],
        ['label' => 'Unit', 'url' => route('organization.unit.index')],
        ['label' => 'Tambah Unit']
    ]" />

    <div class="max-w-2xl mx-auto">
        <x-ui.card title="Daftar Unit Baharu" description="Sila masukkan maklumat unit di bawah">
            <form method="POST" action="{{ route('organization.unit.store') }}" class="space-y-5">
                @csrf

                @include('organization.unit.partials.form', ['unit' => null])

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button href="{{ route('organization.unit.index') }}" variant="secondary">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Simpan Unit
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
