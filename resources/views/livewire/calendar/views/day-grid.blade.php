<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden flex flex-col">
    <!-- Day Header -->
    <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-2xl flex items-center justify-center font-bold text-base {{ $dayData['isToday'] ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200' }}">
                {{ $dayData['carbon']->day }}
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white capitalize">
                    {{ $dayData['carbon']->translatedFormat('l, d F Y') }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ $dayData['allDayEvents']->count() + $dayData['timedEvents']->count() }} aktiviti dijadualkan
                </p>
            </div>
        </div>

        @if (auth()->user()?->hasPermission('calendar.manage'))
            <a
                href="{{ route('calendar.events.create', ['date' => $dayData['date']]) }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-2xs transition-colors"
            >
                <x-icon name="plus" class="w-4 h-4" />
                <span>Tambah Acara Hari Ini</span>
            </a>
        @endif
    </div>

    <!-- All-Day Events Section -->
    @if ($dayData['allDayEvents']->isNotEmpty())
        <div class="p-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/40 space-y-2">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">
                Acara Sepanjang Hari
            </span>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                @foreach ($dayData['allDayEvents'] as $ev)
                    <button
                        type="button"
                        wire:click="openQuickView({{ $ev->id }})"
                        class="p-2.5 rounded-xl text-left bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition-colors flex items-center justify-between"
                    >
                        <div>
                            <span class="text-xs font-bold text-emerald-900 dark:text-emerald-200 block">
                                {{ $ev->tajuk }}
                            </span>
                            @if ($ev->lokasi)
                                <span class="text-[11px] text-emerald-700 dark:text-emerald-400 flex items-center gap-1 mt-0.5">
                                    <x-icon name="map-pin" class="w-3 h-3" />
                                    {{ $ev->lokasi }}
                                </span>
                            @endif
                        </div>
                        <span class="text-[11px] text-emerald-600 font-medium">
                            {{ $ev->attendees->count() }} orang
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Hourly Timeline (08:00 to 18:00) -->
    <div class="relative overflow-y-auto max-h-[640px]">
        <div class="grid grid-cols-[70px_1fr] min-w-[500px] h-[600px] relative">
            <!-- Left Time Gutter -->
            <div class="border-r border-slate-200 dark:border-slate-800 select-none bg-slate-50/20 dark:bg-slate-900/20">
                @for ($hour = 8; $hour <= 18; $hour++)
                    <div class="h-[60px] relative">
                        <span class="absolute -top-2.5 right-3 text-xs font-mono font-medium text-slate-400">
                            {{ sprintf('%02d:00', $hour) }}
                        </span>
                    </div>
                @endfor
            </div>

            <!-- Single Day Column Canvas -->
            <div class="relative">
                <!-- Hourly horizontal guides & empty slot targets -->
                @for ($hour = 8; $hour < 18; $hour++)
                    <a
                        href="{{ route('calendar.events.create', ['date' => $dayData['date'], 'time' => sprintf('%02d:00', $hour)]) }}"
                        class="h-[60px] border-b border-slate-100 dark:border-slate-800/80 block hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group relative px-4"
                        title="Jadualkan pada {{ sprintf('%02d:00', $hour) }}"
                    >
                        <span class="opacity-0 group-hover:opacity-100 text-xs text-emerald-600 font-mono absolute top-2 left-4 font-semibold">
                            + Klik untuk jadualkan aktiviti jam {{ sprintf('%02d:00', $hour) }}
                        </span>
                    </a>
                @endfor

                <!-- Current Time Indicator -->
                @if ($dayData['isToday'])
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
                            <span class="w-3 h-3 rounded-full bg-rose-500 -ml-1.5"></span>
                        </div>
                    @endif
                @endif

                <!-- Timed Event Blocks -->
                @php
                    $categoryStyles = [
                        'MESYUARAT' => 'bg-purple-100/95 text-purple-900 dark:bg-purple-950/90 dark:text-purple-200 border-l-4 border-l-purple-600 hover:bg-purple-200',
                        'BENGKEL' => 'bg-sky-100/95 text-sky-900 dark:bg-sky-950/90 dark:text-sky-200 border-l-4 border-l-sky-600 hover:bg-sky-200',
                        'KURSUS' => 'bg-amber-100/95 text-amber-900 dark:bg-amber-950/90 dark:text-amber-200 border-l-4 border-l-amber-600 hover:bg-amber-200',
                        'LAWATAN_TAPAK' => 'bg-emerald-100/95 text-emerald-900 dark:bg-emerald-950/90 dark:text-emerald-200 border-l-4 border-l-emerald-600 hover:bg-emerald-200',
                        'LAIN_LAIN' => 'bg-slate-200/95 text-slate-900 dark:bg-slate-800/90 dark:text-slate-200 border-l-4 border-l-slate-600 hover:bg-slate-300',
                    ];
                @endphp

                @foreach ($dayData['timedEvents'] as $ev)
                    @php
                        $startH = (int) $ev->mula->format('H');
                        $startM = (int) $ev->mula->format('i');
                        $startMinTotal = ($startH - 8) * 60 + $startM;
                        $startClamped = max(0, min(600, $startMinTotal));

                        $endH = (int) $ev->tamat->format('H');
                        $endM = (int) $ev->tamat->format('i');
                        $endMinTotal = ($endH - 8) * 60 + $endM;
                        $endClamped = max(0, min(600, $endMinTotal));

                        $durationMin = max(30, $endClamped - $startClamped);
                        $topPercent = ($startClamped / 600) * 100;
                        $heightPercent = ($durationMin / 600) * 100;

                        $style = $categoryStyles[$ev->jenis] ?? $categoryStyles['LAIN_LAIN'];
                    @endphp

                    <button
                        type="button"
                        wire:click.stop="openQuickView({{ $ev->id }})"
                        class="absolute left-3 right-3 rounded-xl p-3 text-left overflow-hidden transition-all z-10 shadow-sm cursor-pointer {{ $style }}"
                        style="top: {{ $topPercent }}%; height: {{ $heightPercent }}%;"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs font-bold leading-tight block">
                                    {{ $ev->tajuk }}
                                </span>
                                <span class="text-[11px] font-mono opacity-85 mt-0.5 block">
                                    {{ $ev->mula->format('H:i') }} – {{ $ev->tamat->format('H:i') }} ({{ $ev->mula->diffForHumans($ev->tamat, true) }})
                                </span>
                            </div>
                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-md bg-white/60 dark:bg-black/30">
                                {{ $ev->jenis_label }}
                            </span>
                        </div>

                        @if ($ev->lokasi)
                            <div class="text-[11px] opacity-80 flex items-center gap-1 mt-1 truncate">
                                <x-icon name="map-pin" class="w-3.5 h-3.5 shrink-0" />
                                <span>{{ $ev->lokasi }}</span>
                            </div>
                        @endif

                        @if ($ev->attendees->isNotEmpty())
                            <div class="text-[11px] opacity-80 flex items-center gap-1 mt-1 truncate">
                                <x-icon name="user-group" class="w-3.5 h-3.5 shrink-0" />
                                <span>{{ $ev->attendees->count() }} orang terlibat</span>
                            </div>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>
