<x-layouts.app :title="$event->tajuk">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Kalendar & Aktiviti', 'url' => route('calendar.index')],
        ['label' => $event->tajuk]
    ]" />

    <div class="max-w-3xl mx-auto space-y-6">
        <x-ui.card :title="$event->tajuk" :description="$event->jenis_label">
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ $event->google_calendar_url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-2xs"
                        title="Tambah ke Google Calendar"
                    >
                        <x-icon name="calendar" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                        <span>Google Calendar</span>
                    </a>

                    <x-ui.button href="{{ route('calendar.events.export-ics', $event) }}" variant="secondary" size="sm" title="Muat turun fail .ics">
                        <x-icon name="download" class="w-4 h-4 text-slate-500" />
                        <span>Eksport .ics</span>
                    </x-ui.button>

                    <x-ui.button href="{{ route('attendance.index', ['date' => $event->mula->toDateString()]) }}" variant="secondary" size="sm">
                        <x-icon name="attendance" class="w-4 h-4 text-emerald-600" />
                        <span>Kehadiran Tarikh Ini</span>
                    </x-ui.button>
                    @if (auth()->user()?->hasPermission('calendar.manage'))
                        <x-ui.button href="{{ route('calendar.events.edit', $event) }}" variant="secondary" size="sm">
                            <x-icon name="pencil" class="w-4 h-4 text-emerald-600" />
                            <span>Kemaskini</span>
                        </x-ui.button>
                    @endif
                </div>
            </x-slot:actions>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase">Tarikh & Masa</p>
                        <p class="text-sm font-bold text-slate-900 dark:text-white mt-1">
                            {{ $event->mula->translatedFormat('l, d F Y') }}
                        </p>
                        <p class="text-xs font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">
                            {{ $event->mula->format('H:i') }} - {{ $event->tamat->format('H:i') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase">Lokasi / Tempat</p>
                        <p class="text-sm font-medium text-slate-800 dark:text-slate-200 mt-1">
                            {{ $event->lokasi ?? 'Tidak Dinyatakan' }}
                        </p>
                    </div>
                </div>

                @if ($event->catatan)
                    <div class="pb-4 border-b border-slate-200 dark:border-slate-800">
                        <p class="text-xs font-semibold text-slate-500 uppercase">Catatan / Agenda</p>
                        <p class="text-sm text-slate-800 dark:text-slate-200 mt-1 whitespace-pre-line">{{ $event->catatan }}</p>
                    </div>
                @endif

                <!-- Attendees List -->
                <div>
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-3">
                        Senarai Anggota Terlibat ({{ $event->attendees->count() }} orang)
                    </h4>

                    <div class="divide-y divide-slate-100 dark:divide-slate-800 border border-slate-200 dark:border-slate-800 rounded-lg">
                        @forelse ($event->attendees as $a)
                            <div class="p-3 flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-semibold text-slate-900 dark:text-white">
                                        {{ $a->nama_penuh_gelaran }}
                                    </span>
                                    <span class="text-slate-500 ml-1">
                                        &bull; {{ $a->jawatan ?? 'Anggota' }}
                                    </span>
                                </div>
                                <span class="font-mono text-slate-400">
                                    {{ $a->no_pekerja ?? '-' }}
                                </span>
                            </div>
                        @empty
                            <div class="p-4 text-center text-xs text-slate-400">
                                Tiada anggota direkodkan.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Audit info -->
                <div class="pt-2 text-xs text-slate-400 dark:text-slate-500">
                    Dijadualkan oleh <span class="font-medium text-slate-600 dark:text-slate-400">{{ $event->creator?->nama ?? 'Sistem' }}</span> pada {{ $event->created_at->format('d/m/Y H:i') }}
                </div>
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
