@if ($activeEvent)
    <div
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity animate-in fade-in duration-200"
        wire:keydown.escape.window="closeQuickView"
    >
        <!-- Backdrop click target -->
        <div class="fixed inset-0" wire:click="closeQuickView"></div>

        <!-- Popover Modal Card -->
        <div class="relative bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full overflow-hidden z-10 transition-transform transform scale-100">
            <!-- Colored Banner Header based on event type -->
            @php
                $bannerColors = [
                    'MESYUARAT' => 'bg-purple-600',
                    'BENGKEL' => 'bg-sky-600',
                    'KURSUS' => 'bg-amber-600',
                    'LAWATAN_TAPAK' => 'bg-emerald-600',
                    'LAIN_LAIN' => 'bg-slate-600',
                ];
                $bannerBg = $bannerColors[$activeEvent->jenis] ?? 'bg-slate-600';
            @endphp
            <div class="{{ $bannerBg }} h-3 w-full"></div>

            <div class="p-6 space-y-5">
                <!-- Top controls: Category Pill + Close button -->
                <div class="flex items-center justify-between">
                    <x-ui.badge :variant="$activeEvent->jenis === 'MESYUARAT' ? 'purple' : ($activeEvent->jenis === 'BENGKEL' ? 'info' : ($activeEvent->jenis === 'KURSUS' ? 'warning' : ($activeEvent->jenis === 'LAWATAN_TAPAK' ? 'success' : 'default')))">
                        {{ $activeEvent->jenis_label }}
                    </x-ui.badge>

                    <button
                        type="button"
                        wire:click="closeQuickView"
                        class="p-1.5 rounded-full text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        title="Tutup (Esc)"
                    >
                        <x-icon name="x-mark" class="w-5 h-5" />
                    </button>
                </div>

                <!-- Event Title -->
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-snug">
                        {{ $activeEvent->tajuk }}
                    </h3>
                </div>

                <!-- Date & Time Info -->
                <div class="space-y-2.5 text-sm">
                    <div class="flex items-center gap-3 text-slate-700 dark:text-slate-300">
                        <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0 text-emerald-600 dark:text-emerald-400">
                            <x-icon name="calendar" class="w-4 h-4" />
                        </div>
                        <div>
                            <span class="font-semibold block">
                                {{ $activeEvent->mula->translatedFormat('l, d F Y') }}
                            </span>
                            @if ($activeEvent->mula->toDateString() !== $activeEvent->tamat->toDateString())
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    hingga {{ $activeEvent->tamat->translatedFormat('l, d F Y') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-3 text-slate-700 dark:text-slate-300">
                        <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0 text-emerald-600 dark:text-emerald-400">
                            <x-icon name="clock" class="w-4 h-4" />
                        </div>
                        <div>
                            @if ($activeEvent->sepanjang_hari)
                                <span class="font-medium">Sepanjang Hari</span>
                            @else
                                <span class="font-mono font-medium">
                                    {{ $activeEvent->mula->format('H:i') }} – {{ $activeEvent->tamat->format('H:i') }}
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 ml-1">
                                    ({{ $activeEvent->mula->diffForHumans($activeEvent->tamat, true) }})
                                </span>
                            @endif
                        </div>
                    </div>

                    @if ($activeEvent->lokasi)
                        <div class="flex items-center gap-3 text-slate-700 dark:text-slate-300">
                            <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0 text-emerald-600 dark:text-emerald-400">
                                <x-icon name="map-pin" class="w-4 h-4" />
                            </div>
                            <span class="font-medium">{{ $activeEvent->lokasi }}</span>
                        </div>
                    @endif
                </div>

                <!-- Notes / Catatan -->
                @if ($activeEvent->catatan)
                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-300 space-y-1">
                        <span class="font-bold text-[10px] text-slate-400 uppercase tracking-wider block">
                            Catatan & Agenda
                        </span>
                        <p class="whitespace-pre-line leading-relaxed">{{ $activeEvent->catatan }}</p>
                    </div>
                @endif

                <!-- Attendees Section -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Pegawai Terlibat ({{ $activeEvent->attendees->count() }})
                        </span>
                    </div>

                    <div class="max-h-36 overflow-y-auto space-y-1.5 pr-1">
                        @forelse ($activeEvent->attendees as $att)
                            <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800/60 text-xs">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-6 h-6 rounded-full bg-emerald-600 text-white font-bold text-[10px] flex items-center justify-center shrink-0">
                                        {{ mb_substr($att->nama_penuh, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-semibold text-slate-900 dark:text-white truncate block">
                                            {{ $att->nama_penuh }}
                                        </span>
                                        <span class="text-[10px] text-slate-500 truncate block">
                                            {{ $att->jawatan ?? 'Anggota' }}
                                        </span>
                                    </div>
                                </div>

                                @if ($att->penempatanAktif?->bahagian)
                                    <span class="text-[10px] font-medium text-slate-500 bg-slate-200 dark:bg-slate-700 px-2 py-0.5 rounded-md shrink-0">
                                        {{ $att->penempatanAktif->bahagian->kod ?? $att->penempatanAktif->bahagian->nama }}
                                    </span>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">Tiada anggota disenaraikan.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Creator & Meta -->
                <div class="text-[11px] text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <span>Dijadualkan oleh: <strong class="text-slate-600 dark:text-slate-300">{{ $activeEvent->creator?->nama ?? 'Sistem' }}</strong></span>
                    <span>{{ $activeEvent->created_at->diffForHumans() }}</span>
                </div>
            </div>

            <!-- Footer Actions (SOP Priority 9: Links to Dedicated CRU Pages) -->
            <div class="px-6 py-4 bg-slate-50/70 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    @if (auth()->user()?->hasPermission('calendar.manage'))
                        <form
                            method="POST"
                            action="{{ route('calendar.events.destroy', $activeEvent) }}"
                            onsubmit="return confirm('Adakah anda pasti untuk memadam aktiviti ini?');"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                            >
                                <x-icon name="trash" class="w-4 h-4" />
                                <span>Padam</span>
                            </button>
                        </form>

                        <a
                            href="{{ route('calendar.events.edit', $activeEvent) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                        >
                            <x-icon name="pencil" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                            <span>Kemaskini</span>
                        </a>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <a
                        href="{{ $activeEvent->google_calendar_url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                        title="Buka dan tambah ke Google Calendar"
                    >
                        <x-icon name="calendar" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                        <span>+ Google</span>
                    </a>

                    <a
                        href="{{ route('calendar.events.export-ics', $activeEvent) }}"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                        title="Muat turun fail kalendar .ics"
                    >
                        <x-icon name="download" class="w-3.5 h-3.5 text-slate-500" />
                        <span>.ics</span>
                    </a>

                    <a
                        href="{{ route('calendar.show', $activeEvent) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors"
                    >
                        <span>Butiran</span>
                        <x-icon name="chevron-right" class="w-3.5 h-3.5" />
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif
