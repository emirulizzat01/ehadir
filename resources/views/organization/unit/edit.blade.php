<x-layouts.app title="Kemaskini Unit">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Organisasi'],
        ['label' => 'Unit', 'url' => route('organization.unit.index')],
        ['label' => $unit->kod, 'url' => route('organization.unit.show', $unit)],
        ['label' => 'Kemaskini']
    ]" />

    <div class="max-w-2xl mx-auto">
        <x-ui.card :title="'Kemaskini: ' . $unit->nama" description="Ubah suai butiran unit">
            <x-slot:actions>
                <button
                    type="button"
                    class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline cursor-pointer"
                    x-data
                    @click="$dispatch('open-confirm-modal', { name: 'delete-confirm', action: '{{ route('organization.unit.destroy', $unit) }}' })"
                >
                    Padam Unit
                </button>
            </x-slot:actions>

            <form method="POST" action="{{ route('organization.unit.update', $unit) }}" class="space-y-5">
                @csrf
                @method('PUT')

                @include('organization.unit.partials.form', ['unit' => $unit])

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button href="{{ route('organization.unit.show', $unit) }}" variant="secondary">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Kemaskini Unit
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
