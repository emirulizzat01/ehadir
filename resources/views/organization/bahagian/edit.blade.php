<x-layouts.app title="Kemaskini Bahagian">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Organisasi'],
        ['label' => 'Bahagian', 'url' => route('organization.bahagian.index')],
        ['label' => $bahagian->kod, 'url' => route('organization.bahagian.show', $bahagian)],
        ['label' => 'Kemaskini']
    ]" />

    <div class="max-w-2xl mx-auto">
        <x-ui.card :title="'Kemaskini: ' . $bahagian->nama" description="Ubah suai butiran bahagian">
            <x-slot:actions>
                <button
                    type="button"
                    class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline cursor-pointer"
                    x-data
                    @click="$dispatch('open-confirm-modal', { name: 'delete-confirm', action: '{{ route('organization.bahagian.destroy', $bahagian) }}' })"
                >
                    Padam Bahagian
                </button>
            </x-slot:actions>

            <form method="POST" action="{{ route('organization.bahagian.update', $bahagian) }}" class="space-y-5">
                @csrf
                @method('PUT')

                @include('organization.bahagian.partials.form', ['bahagian' => $bahagian])

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button href="{{ route('organization.bahagian.show', $bahagian) }}" variant="secondary">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Kemaskini Bahagian
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
