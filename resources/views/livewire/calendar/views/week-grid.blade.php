<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden flex flex-col">
    <!-- Sticky Days Header -->
    <div class="grid grid-cols-[60px_repeat(7,1fr)] border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 sticky top-0 z-20">
        <!-- Corner spacer for time gutter -->
        <div class="border-r border-slate-200 dark:border-slate-800 py-3 text-center text-[10px] font-bold text-slate-400 uppercase">
            Masa
        </div>

        <!-- 7 Days headers -->
        @foreach ($weekDays as $day)
            <div class="border-r border-slate-200 dark:border-slate-800 last:border-r-0 py-2.5 px-2 text-center">
                <span class="text-[11px] font-bold uppercase tracking-wider block {{ $day['isToday'] ? 'text-emerald-600 dark:text-emerald-400 font-extrabold' : 'text-slate-500 dark:text-slate-400' }}">
                    {{ $day['dayName'] }}
                </span>
                <button
                    type="button"
                    wire:click="selectDate('{{ $day['date'] }}', 'day')"
                    class="mt-1 h-7 w-7 mx-auto rounded-full flex items-center justify-center text-xs font-bold transition-transform hover:scale-105
                        {{ $day['isToday'] ? 'bg-emerald-600 text-white shadow-xs' : ($day['isSelected'] ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-800 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-800') }}
                    "
                    title="Buka paparan hari {{ $day['date'] }}"
                >
                    {{ $day['dayNumber'] }}
                </button>
            </div>
        @endforeach
    </div>

    <!-- All-Day Events Row (if any) -->
    @php
        $hasAllDayEvents = collect($weekDays)->some(fn ($d) => $d['allDayEvents']->isNotEmpty());
    @endphp
    @if ($hasAllDayEvents)
        <div class="grid grid-cols-[60px_repeat(7,1fr)] border-b border-slate-200 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-900/60 text-xs">
            <div class="border-r border-slate-200 dark:border-slate-800 p-2 text-[10px] font-bold text-slate-400 uppercase text-center flex items-center justify-center">
                Hari Penuh
            </div>
            @foreach ($weekDays as $day)
                <div class="border-r border-slate-200 dark:border-slate-800 last:border-r-0 p-1.5 space-y-1">
                    @foreach ($day['allDayEvents'] as $ev)
                        <button
                            type="button"
                            wire:click.stop="openQuickView({{ $ev->id }})"
                            class="w-full text-left px-2 py-1 rounded text-[11px] font-semibold truncate bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 hover:opacity-90 transition-opacity"
                        >
                            {{ $ev->tajuk }}
                        </button>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif

    <!-- Time Grid (08:00 to 18:00, total 10 hours = 600px height) -->
    <div class="relative overflow-y-auto max-h-[640px]">
        <div class="grid grid-cols-[60px_repeat(7,1fr)] min-w-[700px] h-[600px] relative">
            <!-- Left Time Gutter -->
            <div class="border-r border-slate-200 dark:border-slate-800 select-none bg-slate-50/20 dark:bg-slate-900/20">
                @for ($hour = 8; $hour <= 18; $hour++)
                    <div class="h-[60px] relative">
                        <span class="absolute -top-2.5 right-2 text-[10px] font-mono font-medium text-slate-400">
                            {{ sprintf('%02d:00', $hour) }}
                        </span>
                    </div>
                @endfor
            </div>

            <!-- 7 Day Columns -->
            @foreach ($weekDays as $dayIndex => $day)
                <div class="border-r border-slate-200 dark:border-slate-800 last:border-r-0 relative {{ $day['isToday'] ? 'bg-emerald-50/10 dark:bg-emerald-950/10' : '' }}">
                    <!-- Hourly guide lines & empty slot click targets -->
                    @for ($hour = 8; $hour < 18; $hour++)
                        <a
                            href="{{ route('calendar.events.create', ['date' => $day['date'], 'time' => sprintf('%02d:00', $hour)]) }}"
                            class="h-[60px] border-b border-slate-100 dark:border-slate-800/80 block hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group relative"
                            title="Jadualkan pada {{ sprintf('%02d:00', $hour) }}"
                        >
                            <span class="opacity-0 group-hover:opacity-100 text-[10px] text-emerald-600 font-mono absolute top-1 left-1 font-semibold">
                                + {{ sprintf('%02d:00', $hour) }}
                            </span>
                        </a>
                    @endfor

                    <!-- Current Time Red Line Indicator (if today) -->
                    @if ($day['isToday'])
                        @php
                            $nowHour = (int) now()->format('H');
                            $nowMin = (int) now()->format('i');
                            $currentTotalMin = ($nowHour - 8) * 60 + $nowMin;
                        @endphp
                        @if ($currentTotalMin >= 0 && $currentTotalMin <= 600)
                            <div
                                class="absolute left-0 right-0 z-10 border-t-2 border-rose-500 pointer-events-none flex items-center"
                                style="top: {{ ($currentTotalMin / 600) * 100 }}%;"
                            >
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 -ml-1"></span>
                            </div>
                        @endif
                    @endif

                    <!-- Timed Event Blocks -->
                    @php
                        $categoryStyles = [
                            'MESYUARAT' => 'bg-purple-100/90 text-purple-900 dark:bg-purple-950/80 dark:text-purple-200 border-l-4 border-l-purple-600 shadow-xs hover:bg-purple-200/90',
                            'BENGKEL' => 'bg-sky-100/90 text-sky-900 dark:bg-sky-950/80 dark:text-sky-200 border-l-4 border-l-sky-600 shadow-xs hover:bg-sky-200/90',
                            'KURSUS' => 'bg-amber-100/90 text-amber-900 dark:bg-amber-950/80 dark:text-amber-200 border-l-4 border-l-amber-600 shadow-xs hover:bg-amber-200/90',
                            'LAWATAN_TAPAK' => 'bg-emerald-100/90 text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200 border-l-4 border-l-emerald-600 shadow-xs hover:bg-emerald-200/90',
                            'LAIN_LAIN' => 'bg-slate-200/90 text-slate-900 dark:bg-slate-800/80 dark:text-slate-200 border-l-4 border-l-slate-600 shadow-xs hover:bg-slate-300/90',
                        ];
                    @endphp

                    @foreach ($day['timedEvents'] as $ev)
                        @php
                            $startH = (int) $ev->mula->format('H');
                            $startM = (int) $ev->mula->format('i');
                            $startMinTotal = ($startH - 8) * 60 + $startM;
                            $startClamped = max(0, min(600, $startMinTotal));

                            $endH = (int) $ev->tamat->format('H');
                            $endM = (int) $ev->tamat->format('i');
                            $endMinTotal = ($endH - 8) * 60 + $endM;
                            $endClamped = max(0, min(600, $endMinTotal));

                            $durationMin = max(25, $endClamped - $startClamped);
                            $topPercent = ($startClamped / 600) * 100;
                            $heightPercent = ($durationMin / 600) * 100;

                            $style = $categoryStyles[$ev->jenis] ?? $categoryStyles['LAIN_LAIN'];
                        @endphp

                        <button
                            type="button"
                            wire:click.stop="openQuickView({{ $ev->id }})"
                            class="absolute left-1 right-1 rounded-md p-1.5 text-left text-xs overflow-hidden transition-all z-10 cursor-pointer {{ $style }}"
                            style="top: {{ $topPercent }}%; height: {{ $heightPercent }}%;"
                            title="{{ $ev->tajuk }} ({{ $ev->mula->format('H:i') }} - {{ $ev->tamat->format('H:i') }})"
                        >
                            <div class="font-bold text-[11px] truncate leading-tight">
                                {{ $ev->tajuk }}
                            </div>
                            <div class="text-[10px] font-mono opacity-80 truncate">
                                {{ $ev->mula->format('H:i') }} – {{ $ev->tamat->format('H:i') }}
                            </div>
                            @if ($ev->lokasi && $heightPercent > 8)
                                <div class="text-[10px] opacity-75 truncate flex items-center gap-1 mt-0.5">
                                    <x-icon name="map-pin" class="w-2.5 h-2.5 shrink-0" />
                                    <span>{{ $ev->lokasi }}</span>
                                </div>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>
