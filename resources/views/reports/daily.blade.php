<x-layouts.app title="Laporan Kehadiran Harian">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Laporan'],
        ['label' => 'Laporan Harian']
    ]" />

    <div class="space-y-6">
        <x-ui.card>
            <form method="GET" action="{{ route('reports.daily') }}" class="flex flex-wrap items-end justify-between gap-4">
                <div class="grid w-full grid-cols-1 gap-3 lg:flex lg:w-auto lg:flex-wrap lg:items-end">
                    <div class="min-w-0">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                            Pilih Tarikh Laporan
                        </label>
                        <input
                            type="date"
                            name="tarikh"
                            value="{{ $date }}"
                            onchange="this.form.submit()"
                            class="block w-full min-w-0 rounded-lg border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-900 focus:border-emerald-500 focus:ring-emerald-500 lg:w-auto dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        />
                    </div>

                    @if ($bahagianOptions->isNotEmpty())
                        <div class="min-w-0">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Tapis Bahagian
                            </label>
                            <select
                                name="bahagian_id"
                                onchange="this.form.submit()"
                                class="block w-full min-w-0 rounded-lg border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 lg:w-auto dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            >
                                <option value="">Semua Bahagian</option>
                                @foreach ($bahagianOptions as $b)
                                    <option value="{{ $b->id }}" {{ request('bahagian_id') == $b->id ? 'selected' : '' }}>
                                        {{ $b->kod }} — {{ $b->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($unitOptions->isNotEmpty())
                        <div class="min-w-0">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Tapis Unit
                            </label>
                            <select
                                name="unit_id"
                                onchange="this.form.submit()"
                                class="block w-full min-w-0 rounded-lg border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 lg:w-auto dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            >
                                <option value="">Semua Unit</option>
                                @foreach ($unitOptions as $u)
                                    <option value="{{ $u->id }}" {{ request('unit_id') == $u->id ? 'selected' : '' }}>
                                        {{ $u->kod }} — {{ $u->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <div class="flex w-full flex-wrap items-center gap-2 lg:w-auto lg:justify-end">
                    @include('attendance.partials.whatsapp-modal', ['date' => $date])

                    <x-ui.button href="{{ route('reports.daily.print', ['tarikh' => $date, 'bahagian_id' => request('bahagian_id'), 'unit_id' => request('unit_id')]) }}" variant="secondary" size="sm" target="_blank">
                        <x-icon name="printer" class="w-4 h-4" />
                        <span>Cetak / PDF</span>
                    </x-ui.button>

                    <x-ui.button href="{{ route('reports.monthly') }}" variant="secondary" size="sm">
                        <span>Buku Kehadiran Bulanan</span>
                    </x-ui.button>

                    @if (auth()->user()?->hasPermission('reports.export'))
                        <x-ui.button href="{{ route('reports.export', ['tarikh' => $date]) }}" variant="primary" size="sm">
                            <x-icon name="download" class="w-4 h-4" />
                            <span>Eksport CSV</span>
                        </x-ui.button>
                    @endif
                </div>
            </form>
        </x-ui.card>

        <!-- Printable Report Sheet -->
        <x-ui.card :title="'Laporan Kehadiran Organisasi: ' . \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y')">
            <div class="hidden overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800 xl:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="w-12 px-4 py-3">Bil</th>
                            <th class="px-4 py-3">No. Pekerja</th>
                            <th class="px-4 py-3">Nama Anggota</th>
                            <th class="px-4 py-3">Jawatan</th>
                            <th class="px-4 py-3">Penempatan</th>
                            <th class="px-4 py-3">Status Kehadiran</th>
                            <th class="px-4 py-3">Waktu / Butiran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($anggotaList as $idx => $a)
                            @php
                                $rec = $records->get($a->id);
                            @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3 text-slate-400 font-mono text-xs">{{ ($anggotaList->firstItem() ?? 1) + $idx }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $a->no_pekerja ?? '-' }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">
                                    {{ $a->nama_penuh_gelaran }}
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-xs">{{ $a->jawatan ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-400">
                                    {{ $rec?->penempatan?->lokasi_penempatan ?? 'Tiada Penempatan' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($rec)
                                        <x-ui.badge :color="$rec->status->warna">
                                            {{ $rec->status->nama }}
                                        </x-ui.badge>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Belum Direkod</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    @if ($rec)
                                        {{ $rec->sepanjang_hari ? 'Sepanjang Hari' : "{$rec->masa_mula} - {$rec->masa_tamat}" }}
                                        @if ($rec->lokasi) &bull; {{ $rec->lokasi }} @endif
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-8 text-center text-slate-400">
                                    Tiada data untuk dipaparkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile / tablet report cards -->
            <div class="grid grid-cols-1 gap-3 xl:hidden lg:grid-cols-2">
                @forelse ($anggotaList as $a)
                    @php
                        $rec = $records->get($a->id);
                    @endphp
                    <x-ui.record-card>
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="line-clamp-2 text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ $a->nama_penuh_gelaran }}
                                </p>
                                <p class="mt-0.5 truncate text-[11px] text-slate-500 dark:text-slate-400">
                                    <span class="font-mono">{{ $a->no_pekerja ?? '-' }}</span>
                                    @if ($a->jawatan)
                                        &bull; {{ $a->jawatan }}
                                    @endif
                                </p>
                                <p class="mt-0.5 truncate text-[11px] text-slate-600 dark:text-slate-300">
                                    {{ $rec?->penempatan?->lokasi_penempatan ?? 'Tiada Penempatan' }}
                                </p>
                            </div>
                            @if ($rec)
                                <x-ui.badge :color="$rec->status->warna" class="shrink-0 whitespace-nowrap">
                                    {{ $rec->status->nama }}
                                </x-ui.badge>
                            @else
                                <span class="shrink-0 whitespace-nowrap text-[11px] italic text-slate-400">Belum Direkod</span>
                            @endif
                        </div>

                        <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-slate-100 pt-2 text-[11px] text-slate-500 dark:border-slate-800 dark:text-slate-400">
                            @if ($rec)
                                <span class="inline-flex items-center gap-1">
                                    <x-icon name="clock" class="h-3.5 w-3.5" />
                                    {{ $rec->sepanjang_hari ? 'Sepanjang Hari' : (($rec->masa_mula && $rec->masa_tamat) ? "{$rec->masa_mula} - {$rec->masa_tamat}" : 'Masa tidak dinyatakan') }}
                                </span>
                                @if ($rec->lokasi)
                                    <span class="inline-flex min-w-0 items-center gap-1">
                                        <x-icon name="map-pin" class="h-3.5 w-3.5 shrink-0" />
                                        <span class="truncate">{{ $rec->lokasi }}</span>
                                    </span>
                                @endif
                            @else
                                <span>-</span>
                            @endif
                        </div>
                    </x-ui.record-card>
                @empty
                    <x-ui.empty-state message="Tiada data untuk dipaparkan." class="col-span-full" />
                @endforelse
            </div>

            @if ($anggotaList->hasPages())
                <div class="mt-4">
                    {{ $anggotaList->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>
</x-layouts.app>
