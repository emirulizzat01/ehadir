<x-layouts.app title="Penempatan / Pertukaran Baharu">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Anggota', 'url' => route('personnel.anggota.index')],
        ['label' => $anggota->nama_penuh, 'url' => route('personnel.anggota.show', $anggota)],
        ['label' => 'Penempatan Baharu']
    ]" />

    <div class="max-w-2xl mx-auto">
        <x-ui.card :title="'Penempatan Baharu: ' . $anggota->nama_penuh_gelaran" description="Rekodkan pertukaran bahagian atau unit dengan penguatkuasaan integriti tempoh masa">
            @if ($currentPlacement)
                <div class="mb-5 p-3.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs">
                    <p class="font-semibold text-emerald-800 dark:text-emerald-300">Penempatan Semasa Aktif:</p>
                    <p class="text-emerald-700 dark:text-emerald-400 mt-0.5">
                        {{ $currentPlacement->jenis_penempatan }}: <span class="font-bold">{{ $currentPlacement->lokasi_penempatan }}</span> (Sejak {{ $currentPlacement->tarikh_mula->format('d/m/Y') }})
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ route('personnel.penempatan.store', $anggota) }}" class="space-y-5">
                @csrf

                @include('personnel.penempatan.partials.form', [
                    'anggota' => $anggota,
                    'bahagianList' => $bahagianList,
                    'unitList' => $unitList,
                    'currentPlacement' => $currentPlacement
                ])

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button href="{{ route('personnel.anggota.show', $anggota) }}" variant="secondary">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Sahkan Penempatan
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
