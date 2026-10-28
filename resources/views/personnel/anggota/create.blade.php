<x-layouts.app title="Daftar Anggota">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Anggota', 'url' => route('personnel.anggota.index')],
        ['label' => 'Daftar Anggota']
    ]" />

    <div class="max-w-3xl mx-auto">
        <x-ui.card title="Pendaftaran Anggota Baharu" description="Sila lengkapkan butiran peribadi dan penempatan permulaan">
            <form method="POST" action="{{ route('personnel.anggota.store') }}" class="space-y-6">
                @csrf

                @include('personnel.anggota.partials.form', [
                    'anggota' => null,
                    'bahagianList' => $bahagianList,
                    'unitList' => $unitList,
                    'includePlacement' => true,
                ])

                <!-- Action Buttons -->
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button href="{{ route('personnel.anggota.index') }}" variant="secondary">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Simpan Anggota
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
