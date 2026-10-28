<x-layouts.app title="Paparan Utama">
    <x-slot:header>
        <div class="flex items-center justify-between w-full gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">
                    Ringkasan Kehadiran Harian
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Status keberadaan organisasi pada hari ini, {{ now()->translatedFormat('d F Y') }}
                </p>
            </div>
            @if (auth()->user()?->hasPermission('attendance.view'))
                <a
                    href="{{ route('tv.index') }}"
                    target="_blank"
                    class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-2xs"
                >
                    <x-icon name="arrows-pointing-out" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                    <span>Paparan TV</span>
                </a>
            @endif
        </div>
    </x-slot:header>

    <div class="space-y-6">
        <!-- STATS KPI GRID (SOP Priority 6: Clean, purposeful, minimal) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
            <x-ui.stat-card
                title="Jumlah Anggota Skop"
                :value="$totalAnggota"
                description="Anggota aktif dalam skop anda"
                icon="anggota"
                color="blue"
            />

            <x-ui.stat-card
                title="Hadir Di Pejabat"
                :value="$statusCounts['PEJABAT']['count'] ?? 0"
                :description="'Kadar Kehadiran: ' . number_format($presenceRate, 1) . '%'"
                icon="attendance"
                color="emerald"
            />

            <x-ui.stat-card
                title="Cuti / MC"
                :value="($statusCounts['CUTI_REHAT']['count'] ?? 0) + ($statusCounts['MC']['count'] ?? 0)"
                description="Cuti Rehat & Cuti Sakit"
                icon="calendar"
                color="amber"
            />

            <x-ui.stat-card
                title="Kerja Luar / 4 Jam"
                :value="($statusCounts['KERJA_LUAR']['count'] ?? 0) + ($statusCounts['EMPAT_JAM']['count'] ?? 0)"
                description="Tugas Rasmi & Pelepasan"
                icon="organization"
                color="purple"
            />
        </div>

        <!-- BREAKDOWN & QUICK ACTIONS -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Status Breakdown Card -->
            <div class="lg:col-span-2">
                <x-ui.card title="Pecahan Status Kehadiran Hari Ini" description="Pengagihan status mengikut ketetapan organisasi">
                    <x-slot:actions>
                        <x-ui.button href="{{ route('attendance.index') }}" variant="secondary" size="sm">
                            <x-icon name="attendance" class="w-4 h-4" />
                            <span>Buka Rekod Harian</span>
                        </x-ui.button>
                    </x-slot:actions>

                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3.5 mb-6">
                        @foreach ($allStatuses as $st)
                            @php
                                $cnt = $statusCounts[$st->kod]['count'] ?? 0;
                                $pct = $totalAnggota > 0 ? round(($cnt / $totalAnggota) * 100) : 0;
                            @endphp
                            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="w-3 h-3 rounded-full" style="background-color: {{ $st->warna }};"></span>
                                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 truncate">
                                        {{ $st->nama }}
                                    </span>
                                </div>
                                <div class="flex items-baseline justify-between">
                                    <span class="text-2xl font-bold text-slate-900 dark:text-white">
                                        {{ $cnt }}
                                    </span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $pct }}%
                                    </span>
                                </div>
                            </div>
                        @endforeach

                        <!-- Belum Direkod -->
                        <div class="p-4 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-3 h-3 rounded-full bg-slate-400"></span>
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                                    Belum Direkod
                                </span>
                            </div>
                            <div class="flex items-baseline justify-between">
                                <span class="text-2xl font-bold text-slate-600 dark:text-slate-300">
                                    {{ $unrecordedCount }}
                                </span>
                                <span class="text-xs text-slate-400">
                                    {{ $totalAnggota > 0 ? round(($unrecordedCount / $totalAnggota) * 100) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Progress Bar -->
                    <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-3 overflow-hidden flex">
                        @foreach ($allStatuses as $st)
                            @php
                                $cnt = $statusCounts[$st->kod]['count'] ?? 0;
                                $pct = $totalAnggota > 0 ? ($cnt / $totalAnggota) * 100 : 0;
                            @endphp
                            @if ($pct > 0)
                                <div style="width: {{ $pct }}%; background-color: {{ $st->warna }};" title="{{ $st->nama }}: {{ $cnt }}" class="h-full transition-all"></div>
                            @endif
                        @endforeach
                    </div>
                </x-ui.card>
            </div>

            <!-- Right: Upcoming Events -->
            <div>
                <x-ui.card title="Aktiviti Terdekat" description="7 hari akan datang">
                    <x-slot:actions>
                        <a href="{{ route('calendar.index') }}" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                            Lihat Semua
                        </a>
                    </x-slot:actions>

                    @if ($upcomingEvents->isEmpty())
                        <div class="text-center py-8 text-slate-400 dark:text-slate-500">
                            <x-icon name="calendar" class="w-8 h-8 mx-auto mb-2 opacity-60" />
                            <p class="text-xs">Tiada aktiviti dijadualkan dalam masa terdekat.</p>
                        </div>
                    @else
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($upcomingEvents as $ev)
                                <div class="py-3 first:pt-0 last:pb-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-xs font-semibold text-slate-900 dark:text-white leading-snug">
                                            {{ $ev->tajuk }}
                                        </p>
                                        <x-ui.badge variant="info">
                                            {{ $ev->jenis_label }}
                                        </x-ui.badge>
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
                                        <x-icon name="calendar" class="w-3 h-3 text-emerald-600" />
                                        <span>{{ $ev->mula->format('d/m/Y H:i') }}</span>
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.card>
            </div>
        </div>

        @if (auth()->user()?->isSuperAdmin() && $recentAudits->isNotEmpty())
            <!-- AUDIT TRAIL PREVIEW (SUPERADMIN ONLY) -->
            <x-ui.card title="Aktiviti Sistem Terkini" description="Log keselamatan dan transaksi data audit">
                <x-slot:actions>
                    <a href="{{ route('audit.index') }}" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                        Lihat Log Lengkap
                    </a>
                </x-slot:actions>

                <x-datatable.container>
                    <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <x-datatable.th :sortable="false" label="Masa" />
                            <x-datatable.th :sortable="false" label="Pengguna" />
                            <x-datatable.th :sortable="false" label="Tindakan" />
                            <x-datatable.th :sortable="false" label="Jadual" />
                            <x-datatable.th :sortable="false" label="ID Rekod" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono text-xs">
                        @foreach ($recentAudits as $log)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="px-5 py-3 text-slate-500">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                <td class="px-5 py-3 text-slate-800 dark:text-slate-200 font-sans font-medium">{{ $log->user?->nama ?? 'Sistem' }}</td>
                                <td class="px-5 py-3">
                                    <x-ui.badge :variant="$log->tindakan === 'DELETE' ? 'danger' : ($log->tindakan === 'CREATE' ? 'success' : 'info')">
                                        {{ $log->tindakan }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-5 py-3 text-slate-700 dark:text-slate-300">{{ $log->nama_jadual }}</td>
                                <td class="px-5 py-3 text-slate-500">#{{ $log->record_id ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-datatable.container>

                <div class="space-y-2 xl:hidden">
                    @foreach ($recentAudits as $log)
                        <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                                </p>
                                <x-ui.badge :variant="$log->tindakan === 'DELETE' ? 'danger' : ($log->tindakan === 'CREATE' ? 'success' : 'info')" class="shrink-0 whitespace-nowrap">
                                    {{ $log->tindakan }}
                                </x-ui.badge>
                            </div>
                            <p class="mt-1 truncate text-xs font-semibold text-slate-900 dark:text-white">
                                {{ $log->user?->nama ?? 'Sistem' }}
                            </p>
                            <p class="mt-0.5 truncate font-mono text-[10px] text-slate-500 dark:text-slate-400">
                                {{ $log->nama_jadual }} &bull; #{{ $log->record_id ?? '-' }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        @endif
    </div>
</x-layouts.app>
