<x-layouts.app title="Kemaskini Anggota">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Anggota', 'url' => route('personnel.anggota.index')],
        ['label' => $anggota->nama_penuh, 'url' => route('personnel.anggota.show', $anggota)],
        ['label' => 'Kemaskini']
    ]" />

    <div class="max-w-2xl mx-auto">
        <x-ui.card :title="'Kemaskini: ' . $anggota->nama_penuh_gelaran" description="Ubah suai profil peribadi dan maklumat perjawatan">
            <x-slot:actions>
                <button
                    type="button"
                    class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline cursor-pointer"
                    x-data
                    @click="$dispatch('open-confirm-modal', { name: 'delete-confirm', action: '{{ route('personnel.anggota.destroy', $anggota) }}' })"
                >
                    Padam Anggota
                </button>
            </x-slot:actions>

            <form method="POST" action="{{ route('personnel.anggota.update', $anggota) }}" class="space-y-5">
                @csrf
                @method('PUT')

                @include('personnel.anggota.partials.form', [
                    'anggota' => $anggota,
                    'includePlacement' => false,
                ])

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button href="{{ route('personnel.anggota.show', $anggota) }}" variant="secondary">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Kemaskini Anggota
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
