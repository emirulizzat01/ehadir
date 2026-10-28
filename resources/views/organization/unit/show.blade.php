<x-layouts.app :title="$unit->nama">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Organisasi'],
        ['label' => 'Unit', 'url' => route('organization.unit.index')],
        ['label' => $unit->kod]
    ]" />

    <div class="space-y-6">
        <x-ui.card :title="$unit->nama" description="Maklumat terperinci dan senarai penempatan semasa">
            <x-slot:actions>
                @if (auth()->user()?->hasPermission('org.manage'))
                    <x-ui.button href="{{ route('organization.unit.edit', $unit) }}" variant="secondary" size="sm">
                        <x-icon name="pencil" class="w-4 h-4 text-emerald-600" />
                        <span>Kemaskini</span>
                    </x-ui.button>
                @endif
            </x-slot:actions>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pb-4 border-b border-slate-200 dark:border-slate-800">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Kod Unit</p>
                    <p class="text-base font-mono font-bold text-slate-900 dark:text-white mt-1">{{ $unit->kod }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Status Pengoperasian</p>
                    <div class="mt-1">
                        <x-ui.badge :variant="$unit->aktif ? 'success' : 'default'">
                            {{ $unit->aktif ? 'Aktif' : 'Tidak Aktif' }}
                        </x-ui.badge>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Anggota Semasa</p>
                    <p class="text-base font-bold text-slate-900 dark:text-white mt-1">{{ $activePlacements->count() }} orang</p>
                </div>
            </div>

            <!-- Active Placements Table -->
            <div class="mt-6">
                <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-3">
                    Senarai Anggota Berkhidmat di Unit Ini
                </h4>

                <x-datatable.container>
                    <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <x-datatable.th :sortable="false" label="No. Pekerja" />
                            <x-datatable.th :sortable="false" label="Nama Anggota" />
                            <x-datatable.th :sortable="false" label="Jawatan" />
                            <x-datatable.th :sortable="false" label="Tarikh Mula Berkhidmat" />
                            <x-datatable.th :sortable="false" label="Profil" align="right" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($activePlacements as $p)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3 font-mono text-xs">{{ $p->anggota->no_pekerja ?? '-' }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">
                                    {{ $p->anggota->nama_penuh_gelaran }}
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ $p->anggota->jawatan ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $p->tarikh_mula->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('personnel.anggota.show', $p->anggota) }}" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                                        Lihat Profil &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-400 text-xs">
                                    Tiada anggota ditempatkan di unit ini pada masa ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-datatable.container>

                <div class="grid grid-cols-1 gap-3 xl:hidden lg:grid-cols-2">
                    @forelse ($activePlacements as $p)
                        <x-ui.record-card>
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <a href="{{ route('personnel.anggota.show', $p->anggota) }}" class="line-clamp-2 text-sm font-semibold text-slate-900 hover:text-emerald-600 dark:text-white dark:hover:text-emerald-400">
                                        {{ $p->anggota->nama_penuh_gelaran }}
                                    </a>
                                    <p class="mt-0.5 font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ $p->anggota->no_pekerja ?? '-' }}
                                    </p>
                                </div>
                                <x-icon name="anggota" class="h-4 w-4 shrink-0 text-emerald-500" />
                            </div>

                            <p class="mt-1.5 truncate text-[11px] font-medium text-slate-700 dark:text-slate-300">
                                {{ $p->anggota->jawatan ?? '-' }}
                            </p>
                            <p class="mt-1 flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                                <x-icon name="calendar" class="h-3.5 w-3.5" />
                                Mula {{ $p->tarikh_mula->format('d/m/Y') }}
                            </p>

                            <div class="mt-2.5 flex justify-end border-t border-slate-100 pt-2 dark:border-slate-800">
                                <x-ui.button href="{{ route('personnel.anggota.show', $p->anggota) }}" variant="secondary" size="sm">
                                    <x-icon name="eye" class="h-3.5 w-3.5" />
                                    Lihat Profil
                                </x-ui.button>
                            </div>
                        </x-ui.record-card>
                    @empty
                        <x-ui.empty-state message="Tiada anggota ditempatkan di unit ini pada masa ini." class="col-span-full" />
                    @endforelse
                </div>
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
