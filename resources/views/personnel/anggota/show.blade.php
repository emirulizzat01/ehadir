<x-layouts.app :title="$anggota->nama_penuh">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Anggota', 'url' => route('personnel.anggota.index')],
        ['label' => $anggota->nama_penuh]
    ]" />

    <div class="space-y-6" x-data>
        <!-- Personnel Overview Header Card -->
        <x-ui.card :title="$anggota->nama_penuh_gelaran" :description="($anggota->jawatan ?? 'Anggota Organisasi') . ($anggota->gred ? ' • Gred ' . $anggota->gred : '')">
            <x-slot:actions>
                @if ($anggota->no_pekerja)
                    <form method="POST" action="{{ route('personnel.anggota.refresh-onestack', $anggota) }}" class="inline">
                        @csrf
                        <x-ui.button type="submit" variant="secondary" size="sm" title="Segerakkan semula maklumat terkini daripada OneStack">
                            <x-icon name="history" class="w-4 h-4 text-emerald-600" />
                            <span>Segerak OneStack</span>
                        </x-ui.button>
                    </form>
                @endif
                @if (auth()->user()?->hasPermission('anggota.manage'))
                    <x-ui.button type="button" @click="$dispatch('open-modal', { name: 'modal-create-penempatan' })" variant="primary" size="sm">
                        <x-icon name="plus" class="w-4 h-4" />
                        <span>Penempatan / Pertukaran Baharu</span>
                    </x-ui.button>
                    <x-ui.button type="button" @click="$dispatch('open-slide-over', { name: 'slideover-edit-anggota' })" variant="secondary" size="sm">
                        <x-icon name="pencil" class="w-4 h-4 text-emerald-600" />
                        <span>Kemaskini Profil</span>
                    </x-ui.button>
                @endif
            </x-slot:actions>

            @if ($anggota->onestack_id)
                <div class="mb-4 p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-lg flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-2 text-emerald-900 dark:text-emerald-300">
                        <x-icon name="shield" class="w-4 h-4 text-emerald-600 shrink-0" />
                        <span><strong>Data Induk OneStack:</strong> Profil warga kerja ini dipautkan kepada direktori berpusat OneStack KEJORA (ID Rekod: {{ $anggota->onestack_id }}).</span>
                    </div>
                    <span class="text-emerald-700 dark:text-emerald-400 font-medium font-mono">
                        Selaras: {{ $anggota->last_synced_at ? $anggota->last_synced_at->format('d/m/Y H:i:s') : 'N/A' }}
                    </span>
                </div>
            @endif

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-3">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">No. Pekerja</p>
                    <p class="text-sm font-mono font-bold text-slate-900 dark:text-white mt-1">{{ $anggota->no_pekerja ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Gred Perkhidmatan</p>
                    <p class="text-sm font-bold text-slate-900 dark:text-white mt-1">{{ $anggota->gred ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Emel Rasmi</p>
                    <p class="text-xs font-mono text-slate-700 dark:text-slate-300 mt-1 truncate" title="{{ $anggota->emel }}">{{ $anggota->emel ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">No. Telefon</p>
                    <p class="text-sm font-mono text-slate-700 dark:text-slate-300 mt-1">{{ $anggota->no_telefon ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Status Perkhidmatan</p>
                    <div class="mt-1">
                        <x-ui.badge :variant="$anggota->aktif ? 'success' : 'default'">
                            {{ $anggota->aktif ? 'Aktif Berkhidmat' : 'Tidak Aktif' }}
                        </x-ui.badge>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Tarikh Tamat / Bersara</p>
                    <p class="text-sm font-mono text-slate-700 dark:text-slate-300 mt-1">
                        {{ $anggota->tarikh_bersara ? $anggota->tarikh_bersara->format('d/m/Y') : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Akaun Sistem eHadir</p>
                    <p class="text-xs text-slate-700 dark:text-slate-300 mt-1">
                        @if ($anggota->user)
                            <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
                                <x-icon name="check" class="w-3.5 h-3.5" />
                                <span>{{ $anggota->user->email }}</span>
                            </span>
                        @else
                            <span class="text-slate-400 italic">Tiada pautan pengguna</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Penempatan Semasa</p>
                    <p class="text-xs font-medium text-slate-800 dark:text-slate-200 mt-1">
                        {{ $currentPlacement ? $currentPlacement->lokasi_penempatan : 'Tiada penempatan' }}
                    </p>
                </div>
            </div>
        </x-ui.card>

        <!-- Historical Placement Timeline (Invariant 1: Temporal Integrity) -->
        <x-ui.card title="Garis Masa Sejarah Penempatan" description="Sejarah penempatan dan pertukaran bahagian/unit sepanjang perkhidmatan">
            @if (auth()->user()?->hasPermission('anggota.manage'))
                <x-slot:actions>
                    <x-ui.button
                        type="button"
                        @click="$dispatch('open-modal', { name: 'modal-create-penempatan' })"
                        variant="primary"
                        size="sm"
                    >
                        <x-icon name="plus" class="w-4 h-4" />
                        <span>Penempatan Baharu</span>
                    </x-ui.button>
                </x-slot:actions>
            @endif

            <div class="hidden overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800 xl:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-5 py-3">Entiti Penempatan</th>
                            <th class="px-5 py-3">Jenis</th>
                            <th class="px-5 py-3">Tarikh Mula</th>
                            <th class="px-5 py-3">Tarikh Tamat</th>
                            <th class="px-5 py-3">Status Tempoh</th>
                            @if (auth()->user()?->hasPermission('anggota.manage'))
                                <th class="px-5 py-3 text-right">Tindakan</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($anggota->penempatan as $p)
                            @php
                                $isActive = $p->tarikh_mula->isPast() && ($p->tarikh_tamat === null || $p->tarikh_tamat->isFuture() || $p->tarikh_tamat->isToday());
                            @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                <td class="px-5 py-3.5 font-medium text-slate-900 dark:text-white">
                                    {{ $p->lokasi_penempatan }}
                                </td>
                                <td class="px-5 py-3.5 text-xs text-slate-500">
                                    <span class="px-2 py-0.5 rounded-sm bg-slate-100 dark:bg-slate-800 font-medium">
                                        {{ $p->jenis_penempatan }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 font-mono text-xs text-slate-700 dark:text-slate-300">
                                    {{ $p->tarikh_mula->format('d/m/Y') }}
                                </td>
                                <td class="px-5 py-3.5 font-mono text-xs text-slate-700 dark:text-slate-300">
                                    {{ $p->tarikh_tamat ? $p->tarikh_tamat->format('d/m/Y') : 'Kini (Terbuka)' }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <x-ui.badge :variant="$isActive ? 'success' : 'default'">
                                        {{ $isActive ? 'Sedang Berkuat Kuasa' : 'Selesai / Terbatal' }}
                                    </x-ui.badge>
                                </td>
                                @if (auth()->user()?->hasPermission('anggota.manage'))
                                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                        <x-ui.button href="{{ route('personnel.penempatan.edit', [$anggota, $p]) }}" variant="ghost" size="sm" title="Kemaskini Tarikh">
                                            <x-icon name="pencil" class="w-4 h-4 text-emerald-600" />
                                        </x-ui.button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-slate-400 dark:text-slate-500">
                                    Tiada rekod penempatan direkodkan bagi anggota ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="space-y-2 xl:hidden">
                @forelse ($anggota->penempatan as $p)
                    @php
                        $isActive = $p->tarikh_mula->isPast() && ($p->tarikh_tamat === null || $p->tarikh_tamat->isFuture() || $p->tarikh_tamat->isToday());
                    @endphp
                    <x-ui.record-card>
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="line-clamp-2 text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ $p->lokasi_penempatan }}
                                </p>
                                <span class="mt-1 inline-block rounded-sm bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $p->jenis_penempatan }}
                                </span>
                            </div>
                            <x-ui.badge :variant="$isActive ? 'success' : 'default'" class="shrink-0 whitespace-nowrap">
                                {{ $isActive ? 'Sedang Berkuat Kuasa' : 'Selesai / Terbatal' }}
                            </x-ui.badge>
                        </div>

                        <div class="mt-2.5 grid grid-cols-2 gap-2 border-t border-slate-100 pt-2 text-[11px] dark:border-slate-800">
                            <div>
                                <p class="text-slate-400 dark:text-slate-500">Tarikh Mula</p>
                                <p class="font-mono text-slate-700 dark:text-slate-300">{{ $p->tarikh_mula->format('d/m/Y') }}</p>
                            </div>
                            <div>
                                <p class="text-slate-400 dark:text-slate-500">Tarikh Tamat</p>
                                <p class="font-mono text-slate-700 dark:text-slate-300">{{ $p->tarikh_tamat ? $p->tarikh_tamat->format('d/m/Y') : 'Kini (Terbuka)' }}</p>
                            </div>
                        </div>

                        @if (auth()->user()?->hasPermission('anggota.manage'))
                            <div class="mt-2.5 flex justify-end border-t border-slate-100 pt-2 dark:border-slate-800">
                                <x-ui.button href="{{ route('personnel.penempatan.edit', [$anggota, $p]) }}" variant="secondary" size="sm">
                                    <x-icon name="pencil" class="h-3.5 w-3.5 text-emerald-600" />
                                    Kemaskini Tarikh
                                </x-ui.button>
                            </div>
                        @endif
                    </x-ui.record-card>
                @empty
                    <x-ui.empty-state message="Tiada rekod penempatan direkodkan bagi anggota ini." />
                @endforelse
            </div>
        </x-ui.card>

        <!-- Recent Attendance Log -->
        <x-ui.card title="Sejarah Kehadiran Terkini" description="10 rekod kehadiran terakhir anggota ini">
            <x-slot:actions>
                <x-ui.button href="{{ route('attendance.create', ['anggota_id' => $anggota->id]) }}" variant="secondary" size="sm">
                    <x-icon name="plus" class="w-4 h-4" />
                    <span>Catat Kehadiran</span>
                </x-ui.button>
            </x-slot:actions>

            <div class="hidden overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800 xl:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-4 py-2.5">Tarikh</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5">Masa / Jenis</th>
                            <th class="px-4 py-2.5">Lokasi / Catatan</th>
                            <th class="px-4 py-2.5">Penempatan Terikat</th>
                            <th class="px-4 py-2.5 text-right">Butiran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($anggota->kehadiran as $k)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $k->tarikh->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :color="$k->status->warna">
                                        {{ $k->status->nama }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-400">
                                    {{ $k->sepanjang_hari ? 'Sepanjang Hari' : ($k->masa_mula . ' - ' . $k->masa_tamat) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-400">
                                    {{ $k->lokasi ?? $k->catatan ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ $k->penempatan->lokasi_penempatan ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('attendance.show', $k) }}" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                                        Papar &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-slate-400 text-xs">
                                    Tiada rekod kehadiran direkodkan lagi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="space-y-2 xl:hidden">
                @forelse ($anggota->kehadiran as $k)
                    <details class="group rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                        <summary class="flex cursor-pointer list-none items-center gap-2 p-3 [&::-webkit-details-marker]:hidden">
                            <div class="min-w-0 flex-1">
                                <p class="font-mono text-[11px] font-semibold text-slate-700 dark:text-slate-300">
                                    {{ $k->tarikh->format('d/m/Y') }}
                                </p>
                                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ $k->sepanjang_hari ? 'Sepanjang Hari' : (($k->masa_mula && $k->masa_tamat) ? "{$k->masa_mula} - {$k->masa_tamat}" : 'Masa tidak dinyatakan') }}
                                </p>
                            </div>
                            <x-ui.badge :color="$k->status->warna" class="shrink-0 whitespace-nowrap">
                                {{ $k->status->nama }}
                            </x-ui.badge>
                            <x-icon name="chevron-down" class="h-4 w-4 shrink-0 text-slate-400 transition-transform group-open:rotate-180" />
                        </summary>

                        <div class="border-t border-slate-100 p-3 dark:border-slate-800">
                            <dl class="space-y-1.5 text-[11px]">
                                <div class="flex items-baseline justify-between gap-3">
                                    <dt class="text-slate-500 dark:text-slate-400">Lokasi / Catatan</dt>
                                    <dd class="truncate text-right text-slate-700 dark:text-slate-300">{{ $k->lokasi ?? $k->catatan ?? '-' }}</dd>
                                </div>
                                <div class="flex items-baseline justify-between gap-3">
                                    <dt class="text-slate-500 dark:text-slate-400">Penempatan</dt>
                                    <dd class="truncate text-right text-slate-700 dark:text-slate-300">{{ $k->penempatan->lokasi_penempatan ?? '-' }}</dd>
                                </div>
                            </dl>
                            <div class="mt-2.5 flex justify-end">
                                <x-ui.button href="{{ route('attendance.show', $k) }}" variant="secondary" size="sm">
                                    <x-icon name="eye" class="h-3.5 w-3.5" />
                                    Butiran
                                </x-ui.button>
                            </div>
                        </div>
                    </details>
                @empty
                    <x-ui.empty-state message="Tiada rekod kehadiran direkodkan lagi." />
                @endforelse
            </div>
        </x-ui.card>
    </div>

    @if (auth()->user()?->hasPermission('anggota.manage'))
        <!-- Quick Placement Modal (<5 fields) -->
        <x-ui.modal name="modal-create-penempatan" title="Penempatan Baharu: {{ $anggota->nama_penuh_gelaran }}" description="Rekodkan pertukaran bahagian atau unit dengan penguatkuasaan integriti tempoh masa">
            <form method="POST" action="{{ route('personnel.penempatan.store', $anggota) }}" class="space-y-4">
                @csrf
                @include('personnel.penempatan.partials.form', [
                    'anggota' => $anggota,
                    'bahagianList' => $bahagianList,
                    'unitList' => $unitList,
                    'currentPlacement' => $currentPlacement
                ])
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', { name: 'modal-create-penempatan' })">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Sahkan Penempatan
                    </x-ui.button>
                </div>
            </form>
        </x-ui.modal>

        <!-- Edit Anggota Slide-Over (5-10 fields) -->
        <x-ui.slide-over name="slideover-edit-anggota" title="Kemaskini Profil: {{ $anggota->nama_penuh_gelaran }}" description="Ubah suai profil peribadi dan maklumat perjawatan" width="w-screen max-w-2xl">
            <form method="POST" action="{{ route('personnel.anggota.update', $anggota) }}" class="space-y-5">
                @csrf
                @method('PUT')

                @include('personnel.anggota.partials.form', [
                    'anggota' => $anggota,
                    'includePlacement' => false,
                ])

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button type="button" variant="secondary" @click="$dispatch('close-slide-over', { name: 'slideover-edit-anggota' })">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Kemaskini Anggota
                    </x-ui.button>
                </div>
            </form>
        </x-ui.slide-over>
    @endif
</x-layouts.app>
