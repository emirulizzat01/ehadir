<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden flex flex-col">
    <!-- Day Header (Isnin – Ahad) -->
    <div class="grid grid-cols-7 border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-center py-2.5 text-xs font-bold text-slate-600 dark:text-slate-300">
        <span>Isnin</span>
        <span>Selasa</span>
        <span>Rabu</span>
        <span>Khamis</span>
        <span>Jumaat</span>
        <span class="text-emerald-700 dark:text-emerald-400">Sabtu</span>
        <span class="text-rose-600 dark:text-rose-400">Ahad</span>
    </div>

    <!-- Weeks Rows -->
    <div class="divide-y divide-slate-200 dark:divide-slate-800 flex-1 flex flex-col">
        @foreach ($monthWeeks as $weekIndex => $week)
            <div class="grid grid-cols-7 divide-x divide-slate-200 dark:divide-slate-800 min-h-[130px] flex-1">
                @foreach ($week as $dayIndex => $day)
                    @php
                        $isWeekend = ($dayIndex >= 5);
                        $categoryStyles = [
                            'MESYUARAT' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-l-2 border-l-purple-500 hover:bg-purple-100 dark:hover:bg-purple-900/60',
                            'BENGKEL' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border-l-2 border-l-sky-500 hover:bg-sky-100 dark:hover:bg-sky-900/60',
                            'KURSUS' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-l-2 border-l-amber-500 hover:bg-amber-100 dark:hover:bg-amber-900/60',
                            'LAWATAN_TAPAK' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-l-2 border-l-emerald-500 hover:bg-emerald-100 dark:hover:bg-emerald-900/60',
                            'LAIN_LAIN' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-l-2 border-l-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700',
                        ];
                    @endphp

                    <div
                        class="p-1.5 sm:p-2 flex flex-col justify-between transition-colors group relative
                            {{ ! $day['isCurrentMonth'] ? 'bg-slate-50/50 dark:bg-slate-950/30 opacity-60' : ($isWeekend ? 'bg-slate-50/20 dark:bg-slate-900/40' : 'bg-white dark:bg-slate-900') }}
                        "
                    >
                        <!-- Cell Date Header -->
                        <div class="flex items-center justify-between mb-1.5">
                            <button
                                type="button"
                                wire:click="selectDate('{{ $day['date'] }}', 'day')"
                                class="h-6 w-6 rounded-full flex items-center justify-center text-xs font-semibold transition-transform group-hover:scale-105
                                    @if ($day['isToday'])
                                        bg-emerald-600 text-white font-bold shadow-xs
                                    @elseif ($day['isSelected'])
                                        bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold
                                    @elseif (! $day['isCurrentMonth'])
                                        text-slate-400 dark:text-slate-600
                                    @else
                                        text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-800
                                    @endif
                                "
                                title="Buka paparan hari {{ $day['date'] }}"
                            >
                                {{ $day['dayNumber'] }}
                            </button>

                            @if (auth()->user()?->hasPermission('calendar.manage'))
                                <a
                                    href="{{ route('calendar.events.create', ['date' => $day['date']]) }}"
                                    class="opacity-0 group-hover:opacity-100 p-0.5 rounded text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-opacity"
                                    title="Tambah aktiviti pada {{ $day['date'] }}"
                                >
                                    <x-icon name="plus" class="w-3.5 h-3.5" />
                                </a>
                            @endif
                        </div>

                        <!-- Event Pills List -->
                        <div class="space-y-1 flex-1 overflow-hidden">
                            @php
                                $displayEvents = array_slice($day['events'], 0, 3);
                                $remainingCount = count($day['events']) - 3;
                            @endphp

                            @foreach ($displayEvents as $ev)
                                @php
                                    $style = $categoryStyles[$ev->jenis] ?? $categoryStyles['LAIN_LAIN'];
                                @endphp
                                <button
                                    type="button"
                                    wire:click.stop="openQuickView({{ $ev->id }})"
                                    class="w-full text-left px-1.5 py-0.5 rounded text-[11px] leading-tight font-medium truncate flex items-center gap-1 transition-all shadow-2xs {{ $style }}"
                                    title="{{ $ev->tajuk }} ({{ $ev->mula->format('H:i') }} - {{ $ev->tamat->format('H:i') }})"
                                >
                                    @if (! $ev->sepanjang_hari)
                                        <span class="font-mono text-[10px] opacity-75 shrink-0">{{ $ev->mula->format('H:i') }}</span>
                                    @endif
                                    <span class="truncate">{{ $ev->tajuk }}</span>
                                </button>
                            @endforeach

                            @if ($remainingCount > 0)
                                <button
                                    type="button"
                                    wire:click.stop="selectDate('{{ $day['date'] }}', 'day')"
                                    class="w-full text-left px-1.5 py-0.5 text-[10px] font-bold text-slate-500 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                                >
                                    +{{ $remainingCount }} lagi...
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
