<x-layouts.app title="Buku Kehadiran Bulanan">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Laporan', 'url' => route('reports.daily')],
        ['label' => 'Buku Kehadiran Bulanan']
    ]" />

    <div class="space-y-6">
        <x-ui.card>
            <form method="GET" action="{{ route('reports.monthly') }}" class="flex flex-wrap items-end justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                            Bulan
                        </label>
                        <select name="month" onchange="this.form.submit()" class="rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3.5 py-2">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                            Tahun
                        </label>
                        <select name="year" onchange="this.form.submit()" class="rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3.5 py-2">
                            @for ($y = now()->year - 2; $y <= now()->year + 1; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <x-ui.button href="{{ route('reports.monthly.print', ['year' => $year, 'month' => $month]) }}" variant="secondary" size="sm" target="_blank">
                        <x-icon name="printer" class="w-4 h-4" />
                        <span>Cetak / PDF</span>
                    </x-ui.button>

                    <x-ui.button href="{{ route('reports.daily') }}" variant="secondary" size="sm">
                        <span>Laporan Harian</span>
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <!-- Legend -->
        <div class="flex flex-wrap items-center gap-4 p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs">
            <span class="font-semibold text-slate-700 dark:text-slate-300">Petunjuk Status:</span>
            @foreach ($statuses as $st)
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full" style="background-color: {{ $st->warna }};"></span>
                    <span class="text-slate-600 dark:text-slate-400">{{ $st->nama }} ({{ substr($st->kod, 0, 1) }})</span>
                </div>
            @endforeach
        </div>

        <!-- Matrix Sheet -->
        <x-ui.card :title="'Matriks Kehadiran: ' . $startOfMonth->translatedFormat('F Y')" description="Paparan penuh status harian seluruh warga kerja">
            <div class="hidden overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800 min-[1440px]:block">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-3 py-2.5 sticky left-0 bg-slate-50 dark:bg-slate-800 z-10 w-44">Nama Anggota</th>
                            @for ($d = 1; $d <= $daysInMonth; $d++)
                                @php
                                    $dayCarbon = \Carbon\Carbon::create($year, $month, $d);
                                    $isWeekend = $dayCarbon->isWeekend();
                                @endphp
                                <th class="px-1.5 py-2.5 text-center font-mono {{ $isWeekend ? 'bg-slate-100 dark:bg-slate-800 text-slate-400' : 'text-slate-700 dark:text-slate-300' }}">
                                    {{ $d }}
                                </th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($anggotaList as $a)
                            @php
                                $staffRecords = $records->get($a->id, collect())->keyBy(fn ($r) => $r->tarikh->format('j'));
                            @endphp
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="px-3 py-2 font-medium text-slate-900 dark:text-white sticky left-0 bg-white dark:bg-slate-900 z-10 truncate max-w-[180px]">
                                    {{ $a->nama_penuh }}
                                </td>
                                @for ($d = 1; $d <= $daysInMonth; $d++)
                                    @php
                                        $rec = $staffRecords->get($d);
                                        $dayCarbon = \Carbon\Carbon::create($year, $month, $d);
                                        $isWeekend = $dayCarbon->isWeekend();
                                    @endphp
                                    <td class="px-1 py-1.5 text-center {{ $isWeekend ? 'bg-slate-50 dark:bg-slate-900/60' : '' }}">
                                        @if ($rec)
                                            <span
                                                class="inline-block w-5 h-5 rounded-md text-[10px] font-bold leading-5 text-white shadow-2xs"
                                                style="background-color: {{ $rec->status->warna }};"
                                                title="{{ $a->nama_penuh }}: {{ $rec->status->nama }} ({{ $d }}/{{ $month }})"
                                            >
                                                {{ substr($rec->status->kod, 0, 1) }}
                                            </span>
                                        @elseif ($isWeekend)
                                            <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                                        @else
                                            <span class="text-slate-300 dark:text-slate-700">-</span>
                                        @endif
                                    </td>
                                @endfor
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $daysInMonth + 1 }}" class="px-4 py-8 text-center text-slate-400">
                                    Tiada data anggota.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile / tablet employee accordion -->
            <div class="min-[1440px]:hidden">
                <p class="mb-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Ketuk nama anggota untuk melihat kalendar kehadiran bulanan.
                </p>

                <div class="space-y-2">
                    @forelse ($mobileAnggota as $a)
                        @php
                            $staffRecords = $records->get($a->id, collect())->keyBy(fn ($r) => (int) $r->tarikh->format('j'));
                            $counts = $staffRecords->groupBy(fn ($r) => $r->status->kod)->map->count();
                            $leading = $startOfMonth->dayOfWeekIso - 1;
                            $trailing = (7 - (($leading + $daysInMonth) % 7)) % 7;
                            $hasCounts = false;
                        @endphp
                        <details class="group rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                            <summary class="flex cursor-pointer list-none items-center gap-2 p-3 [&::-webkit-details-marker]:hidden">
                                <div class="min-w-0 flex-1">
                                    <p class="line-clamp-2 text-sm font-semibold text-slate-900 dark:text-white">
                                        {{ $a->nama_penuh }}
                                    </p>
                                    <p class="mt-0.5 truncate font-mono text-[10px] text-slate-500 dark:text-slate-400">
                                        {{ $a->no_pekerja ?? '-' }}
                                    </p>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[10px]">
                                        @foreach ($statuses as $st)
                                            @if (($counts[$st->kod] ?? 0) > 0)
                                                @php $hasCounts = true; @endphp
                                                <span class="font-semibold" style="color: {{ $st->warna }};">
                                                    {{ $counts[$st->kod] }} {{ substr($st->kod, 0, 1) }}
                                                </span>
                                            @endif
                                        @endforeach
                                        @if (! $hasCounts)
                                            <span class="italic text-slate-400 dark:text-slate-500">Tiada rekod</span>
                                        @endif
                                    </div>
                                </div>
                                <x-icon name="chevron-down" class="h-4 w-4 shrink-0 text-slate-400 transition-transform group-open:rotate-180" />
                            </summary>

                            <div class="border-t border-slate-100 p-3 dark:border-slate-800">
                                <div class="grid grid-cols-7 gap-1 text-center">
                                    @foreach (['I', 'S', 'R', 'K', 'J', 'S', 'A'] as $dayName)
                                        <div class="text-[9px] font-semibold uppercase text-slate-400 dark:text-slate-500">{{ $dayName }}</div>
                                    @endforeach

                                    @for ($i = 0; $i < $leading; $i++)
                                        <div aria-hidden="true"></div>
                                    @endfor

                                    @for ($d = 1; $d <= $daysInMonth; $d++)
                                        @php
                                            $rec = $staffRecords->get($d);
                                            $dayCarbon = $startOfMonth->copy()->day($d);
                                            $isWeekend = $dayCarbon->isWeekend();
                                        @endphp
                                        <div class="flex flex-col items-center gap-0.5 rounded-md py-1 {{ $isWeekend ? 'bg-slate-50 dark:bg-slate-800/40' : '' }}">
                                            <span class="font-mono text-[9px] {{ $isWeekend ? 'text-slate-400 dark:text-slate-500' : 'text-slate-600 dark:text-slate-300' }}">{{ $d }}</span>
                                            @if ($rec)
                                                <span
                                                    class="flex h-4 w-4 items-center justify-center rounded-full text-[8px] font-bold text-white"
                                                    style="background-color: {{ $rec->status->warna }};"
                                                    title="{{ $rec->status->nama }}"
                                                >
                                                    {{ substr($rec->status->kod, 0, 1) }}
                                                </span>
                                            @else
                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-200 dark:bg-slate-700"></span>
                                            @endif
                                        </div>
                                    @endfor

                                    @for ($i = 0; $i < $trailing; $i++)
                                        <div aria-hidden="true"></div>
                                    @endfor
                                </div>
                            </div>
                        </details>
                    @empty
                        <x-ui.empty-state message="Tiada data anggota." />
                    @endforelse
                </div>

                @if ($mobileAnggota->hasPages())
                    <div class="mt-4">
                        {{ $mobileAnggota->links() }}
                    </div>
                @endif
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
