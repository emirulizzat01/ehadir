<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
    <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                Jadual Agenda Aktiviti
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Susunan aktiviti mengikut susunan kronologi tarikh
            </p>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
            {{ $agendaGroups->flatten()->count() }} aktiviti dijumpai
        </span>
    </div>

    @if ($agendaGroups->isEmpty())
        <div class="p-12 text-center text-slate-400 dark:text-slate-500 space-y-3">
            <x-icon name="calendar" class="w-12 h-12 mx-auto opacity-40" />
            <p class="text-sm font-medium">Tiada sebarang aktiviti dijadualkan bagi tempoh atau kriteria tapisan ini.</p>
            @if (auth()->user()?->hasPermission('calendar.manage'))
                <a
                    href="{{ route('calendar.events.create', ['date' => $currentDate]) }}"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors"
                >
                    <x-icon name="plus" class="w-4 h-4" />
                    <span>Jadualkan Aktiviti Sekarang</span>
                </a>
            @endif
        </div>
    @else
        <div class="divide-y divide-slate-200 dark:divide-slate-800">
            @foreach ($agendaGroups as $dateString => $events)
                @php
                    $groupCarbon = \Carbon\Carbon::parse($dateString);
                    $isToday = ($dateString === now()->toDateString());
                @endphp

                <div class="p-4 sm:p-5 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                    <!-- Date Group Header -->
                    <div class="flex items-center gap-3 mb-3">
                        <div class="h-8 w-8 rounded-xl flex items-center justify-center font-bold text-xs {{ $isToday ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                            {{ $groupCarbon->day }}
                        </div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white capitalize">
                                {{ $groupCarbon->translatedFormat('l, d F Y') }}
                            </h4>
                            @if ($isToday)
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                    Hari Ini
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Events list under this date -->
                    <div class="space-y-2.5 sm:ml-11">
                        @foreach ($events as $ev)
                            @php
                                $badgeVariant = match ($ev->jenis) {
                                    'MESYUARAT' => 'purple',
                                    'BENGKEL' => 'info',
                                    'KURSUS' => 'warning',
                                    'LAWATAN_TAPAK' => 'success',
                                    default => 'default',
                                };
                            @endphp

                            <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-wrap items-center justify-between gap-3 shadow-2xs hover:border-emerald-300 dark:hover:border-emerald-800 transition-colors">
                                <div class="min-w-0 flex-1 space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-ui.badge :variant="$badgeVariant">
                                            {{ $ev->jenis_label }}
                                        </x-ui.badge>
                                        <button
                                            type="button"
                                            wire:click="openQuickView({{ $ev->id }})"
                                            class="text-sm font-bold text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 text-left cursor-pointer"
                                        >
                                            {{ $ev->tajuk }}
                                        </button>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                                        <span class="flex items-center gap-1 font-mono font-medium text-emerald-700 dark:text-emerald-400">
                                            <x-icon name="clock" class="w-3.5 h-3.5" />
                                            @if ($ev->sepanjang_hari)
                                                Sepanjang Hari
                                            @else
                                                {{ $ev->mula->format('H:i') }} – {{ $ev->tamat->format('H:i') }}
                                            @endif
                                        </span>

                                        @if ($ev->lokasi)
                                            <span class="flex items-center gap-1 truncate">
                                                <x-icon name="map-pin" class="w-3.5 h-3.5 text-slate-400" />
                                                {{ $ev->lokasi }}
                                            </span>
                                        @endif

                                        <span class="flex items-center gap-1">
                                            <x-icon name="user-group" class="w-3.5 h-3.5 text-slate-400" />
                                            {{ $ev->attendees->count() }} orang terlibat
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1 shrink-0">
                                    <button
                                        type="button"
                                        wire:click="openQuickView({{ $ev->id }})"
                                        class="p-2 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                        title="Pratonton Pantas"
                                    >
                                        <x-icon name="eye" class="w-4 h-4" />
                                    </button>
                                    <a
                                        href="{{ route('calendar.show', $ev) }}"
                                        class="p-2 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                        title="Butiran Penuh"
                                    >
                                        <x-icon name="chevron-right" class="w-4 h-4" />
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
