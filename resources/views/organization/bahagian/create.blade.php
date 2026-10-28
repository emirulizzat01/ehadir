<x-layouts.app title="Tambah Bahagian">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Organisasi'],
        ['label' => 'Bahagian', 'url' => route('organization.bahagian.index')],
        ['label' => 'Tambah Bahagian']
    ]" />

    <div class="max-w-2xl mx-auto">
        <x-ui.card title="Daftar Bahagian Baharu" description="Sila masukkan maklumat bahagian di bawah">
            <form method="POST" action="{{ route('organization.bahagian.store') }}" class="space-y-5">
                @csrf

                @include('organization.bahagian.partials.form', ['bahagian' => null])

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button href="{{ route('organization.bahagian.index') }}" variant="secondary">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Simpan Bahagian
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
