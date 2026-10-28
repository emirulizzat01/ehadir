<div
    wire:poll.15s
    x-data="{
        currentTime: '',
        activeDeptPage: 0,
        deptsPerPage: 6,
        totalDepts: {{ count($departmentStats ?? []) }},
        activeRosterPage: 0,
        rosterPerPage: 8,
        totalStaffRoster: {{ count($staffRoster ?? []) }},
        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);

            // Auto-rotate executive department pages every 8 seconds if > 6 departments
            if (this.totalDepts > this.deptsPerPage) {
                setInterval(() => {
                    const maxPages = Math.ceil(this.totalDepts / this.deptsPerPage);
                    this.activeDeptPage = (this.activeDeptPage + 1) % maxPages;
                }, 8000);
            }

            // Auto-rotate department staff roster pages every 10 seconds if > 8 staff
            if (this.totalStaffRoster > this.rosterPerPage) {
                setInterval(() => {
                    const maxRosterPages = Math.ceil(this.totalStaffRoster / this.rosterPerPage);
                    this.activeRosterPage = (this.activeRosterPage + 1) % maxRosterPages;
                }, 10000);
            }
        },
        updateClock() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('ms-MY', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
        },
        handleKeyDown(e) {
            const tag = (e.target.tagName || '').toLowerCase();
            if (['input', 'textarea', 'select'].includes(tag) || e.target.isContentEditable) {
                return;
            }
            if (e.key === 't' || e.key === 'T') {
                e.preventDefault();
                toggleTheme();
            } else if (e.key === 'f' || e.key === 'F') {
                e.preventDefault();
                toggleFullscreen();
            }
        }
    }"
    @keydown.window="handleKeyDown($event)"
    class="flex-1 flex flex-col justify-between p-4 lg:p-6 space-y-4 max-h-screen overflow-hidden"
>
    <!-- ZONE 1: TOP CONTROL BAR -->
    <header class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800/80 pb-3 shrink-0">
        <!-- Brand & Title -->
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 flex items-center justify-center text-white font-black text-xl shadow-lg shadow-emerald-900/30 shrink-0">
                eH
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-lg lg:text-xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                        @if ($mode === 'executive')
                            Pusat Pemantauan Operasi & Kehadiran
                        @elseif ($mode === 'department')
                            Paparan Operasi: {{ $currentBahagian->nama ?? 'Bahagian' }}
                        @else
                            Selamat Datang — Kompleks Pentadbiran eHadir
                        @endif
                    </h1>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800">
                        @if ($mode === 'executive')
                            Eksekutif
                        @elseif ($mode === 'department')
                            {{ $currentBahagian->kod ?? 'Bahagian' }}
                        @else
                            Lobi Awam
                        @endif
                    </span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                    @if ($mode === 'executive')
                        Sistem Kehadiran Organisasi &middot; Paparan Skrin Kawalan Makro Masa Nyata
                    @elseif ($mode === 'department')
                        Pemantauan Status Pegawai & Pergerakan Bahagian Semasa
                    @else
                        Paparan Kesiapsiagaan Perkhidmatan & Maklumat Pelawat
                    @endif
                </p>
            </div>
        </div>

        <!-- Live Pulse Indicator -->
        <div class="hidden xl:flex items-center gap-2 px-3 py-1.5 rounded-full bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
            <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">
                Siaran Langsung
            </span>
            <span class="text-[11px] text-slate-400 dark:text-slate-500 font-mono pl-1 border-l border-slate-200 dark:border-slate-800">
                {{ $lastUpdated }}
            </span>
        </div>

        <!-- Mode Switcher (Authorized Non-Kiosk Users) -->
        @if ($canSwitchModes)
            <div class="hidden md:flex items-center gap-1.5 p-1 rounded-xl bg-slate-200/80 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 text-xs font-semibold">
                <button
                    type="button"
                    wire:click="setMode('executive')"
                    class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer {{ $mode === 'executive' ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                >
                    Eksekutif
                </button>

                <button
                    type="button"
                    wire:click="setMode('department')"
                    class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer {{ $mode === 'department' ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                >
                    Bahagian
                </button>

                <button
                    type="button"
                    wire:click="setMode('lobby')"
                    class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer {{ $mode === 'lobby' ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                >
                    Lobi
                </button>

                @if ($mode === 'department' && count($bahagianList) > 1)
                    <select
                        wire:change="setBahagian($event.target.value)"
                        class="text-xs py-1 px-2 rounded-lg border-0 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-medium focus:ring-1 focus:ring-emerald-500"
                    >
                        @foreach ($bahagianList as $b)
                            <option value="{{ $b->id }}" {{ ($currentBahagian->id ?? null) === $b->id ? 'selected' : '' }}>
                                {{ $b->kod }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>
        @endif

        <!-- Digital Clock & Actions -->
        <div class="flex items-center gap-3 sm:gap-4">
            <div class="text-right">
                <div class="text-xl lg:text-2xl font-black font-mono tracking-wider text-emerald-600 dark:text-emerald-400 leading-none" x-text="currentTime">
                    --:--:--
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                    {{ $formattedDate }}
                </div>
            </div>

            <!-- Theme Toggler (SOP Priority 8 & Kiosk Usability) -->
            <button
                type="button"
                @click="toggleTheme()"
                class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-2xs cursor-pointer"
                title="Tukar Mod Tema Terang/Gelap (Kekunci T)"
            >
                <span class="dark:hidden"><x-icon name="moon" class="w-5 h-5 text-slate-700" /></span>
                <span class="hidden dark:inline"><x-icon name="sun" class="w-5 h-5 text-amber-400" /></span>
            </button>

            <!-- Fullscreen Toggle Button -->
            <button
                type="button"
                @click="toggleFullscreen()"
                class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-2xs cursor-pointer"
                title="Skrin Penuh (Kekunci F atau F11)"
            >
                <x-icon name="arrows-pointing-out" class="w-5 h-5" />
            </button>

            <!-- Back to Portal Link (Hidden in unmanned Kiosk mode) -->
            @if (! $isKiosk)
                <a
                    href="{{ route('dashboard') }}"
                    class="hidden md:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-2xs"
                    title="Kembali ke Portal eHadir"
                >
                    <x-icon name="arrow-left" class="w-4 h-4" />
                    <span>Portal</span>
                </a>
            @endif
        </div>
    </header>

    {{-- ========================================================================= --}}
    {{-- MODE 1: EXECUTIVE / AGENCY-WIDE COMMAND CENTER                            --}}
    {{-- ========================================================================= --}}
    @if ($mode === 'executive')
        <!-- ZONE 2: HERO STATISTIC COUNTERS (5 Glanceable Cards) -->
        <section class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3.5 shrink-0">
            <!-- 1. Overall Presence Rate -->
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-xs dark:shadow-md relative overflow-hidden">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    <span>Kadar Kehadiran</span>
                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/80">
                        KPI: &ge;85%
                    </span>
                </div>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-3xl lg:text-4xl font-black font-mono {{ $presenceRate >= 85 ? 'text-emerald-600 dark:text-emerald-400' : ($presenceRate >= 70 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
                        {{ number_format($presenceRate, 1) }}%
                    </span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 mt-2.5 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500 {{ $presenceRate >= 85 ? 'bg-emerald-500' : ($presenceRate >= 70 ? 'bg-amber-500' : 'bg-rose-500') }}"
                        style="width: {{ min(100, $presenceRate) }}%;"
                    ></div>
                </div>
            </div>

            <!-- 2. Present In Office -->
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-xs dark:shadow-md">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    <span>Hadir Di Pejabat</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                </div>
                <div class="flex items-baseline gap-1.5 mt-1">
                    <span class="text-3xl lg:text-4xl font-black font-mono text-slate-900 dark:text-white">
                        {{ $presentCount }}
                    </span>
                    <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">
                        / {{ $totalStaff }} orang
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 truncate">
                    {{ $unrecordedCount }} belum direkodkan
                </p>
            </div>

            <!-- 3. Duty Out / Training -->
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-xs dark:shadow-md">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    <span>Tugas Luar & Kursus</span>
                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                </div>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-3xl lg:text-4xl font-black font-mono text-purple-600 dark:text-purple-400">
                        {{ $dutyCount }}
                    </span>
                    <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">orang</span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 truncate">
                    Tugas rasmi luar & latihan
                </p>
            </div>

            <!-- 4. Leave & MC -->
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-xs dark:shadow-md">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    <span>Cuti & MC</span>
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                </div>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-3xl lg:text-4xl font-black font-mono text-amber-600 dark:text-amber-400">
                        {{ $leaveCount }}
                    </span>
                    <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">orang</span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 truncate">
                    Cuti rehat & cuti perubatan
                </p>
            </div>

            <!-- 5. Today's Scheduled Events -->
            <div class="col-span-2 md:col-span-1 p-4 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-xs dark:shadow-md">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    <span>Aktiviti Hari Ini</span>
                    @if ($liveEventsCount > 0)
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-400 border border-rose-300 dark:border-rose-800 animate-pulse">
                            {{ $liveEventsCount }} Live
                        </span>
                    @endif
                </div>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-3xl lg:text-4xl font-black font-mono text-sky-600 dark:text-sky-400">
                        {{ $todayEvents->count() }}
                    </span>
                    <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">program</span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 truncate">
                    Mesyuarat, bengkel & lawatan
                </p>
            </div>
        </section>

        <!-- ZONE 3: MAIN OPERATIONAL SPLIT CANVAS -->
        <main class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-4 min-h-0 overflow-hidden">
            <!-- LEFT PANEL: Prestasi Kehadiran Mengikut Bahagian (7 of 12 cols = ~58%) -->
            <section class="lg:col-span-7 flex flex-col rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 p-4 shadow-xs dark:shadow-lg overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5 mb-3">
                    <div class="flex items-center gap-2">
                        <x-icon name="organization" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                        <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Prestasi Kehadiran Mengikut Bahagian
                        </h2>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 font-mono">
                        <span>{{ count($departmentStats) }} Bahagian</span>
                        <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">&ge;85% Cemerlang</span>
                    </div>
                </div>

                <!-- Department Cards Grid (Auto-paginated smoothly via Alpine) -->
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-2.5 overflow-hidden">
                    @foreach ($departmentStats as $index => $dept)
                        <div
                            x-show="Math.floor({{ $index }} / deptsPerPage) === activeDeptPage"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="p-3.5 rounded-xl bg-slate-50/80 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800/90 flex flex-col justify-between shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-colors"
                        >
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate max-w-[180px]" title="{{ $dept['nama'] }}">
                                        {{ $dept['kod'] }} - {{ $dept['nama'] }}
                                    </span>
                                    <span class="text-xs font-mono font-black px-2 py-0.5 rounded-md
                                        @if ($dept['health'] === 'emerald')
                                            bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/60
                                        @elseif ($dept['health'] === 'amber')
                                            bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-400 border border-amber-300 dark:border-amber-800/60
                                        @else
                                            bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-400 border border-rose-300 dark:border-rose-800/60
                                        @endif
                                    ">
                                        {{ number_format($dept['rate'], 1) }}%
                                    </span>
                                </div>

                                <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden my-2">
                                    <div
                                        class="h-full rounded-full transition-all duration-500
                                            @if ($dept['health'] === 'emerald') bg-emerald-500
                                            @elseif ($dept['health'] === 'amber') bg-amber-500
                                            @else bg-rose-500 @endif
                                        "
                                        style="width: {{ min(100, $dept['rate']) }}%;"
                                    ></div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 pt-1 border-t border-slate-200 dark:border-slate-900 font-mono">
                                <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ $dept['present'] }} Hadir</span>
                                <span class="text-purple-600 dark:text-purple-400">{{ $dept['duty'] }} Luar</span>
                                <span class="text-amber-600 dark:text-amber-400">{{ $dept['leave'] }} Cuti</span>
                                <span class="text-slate-400 dark:text-slate-500">{{ $dept['total'] }} Jmlh</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if (count($departmentStats) > 6)
                    <div class="flex items-center justify-center gap-1.5 pt-2 border-t border-slate-200 dark:border-slate-800/60 mt-2">
                        @for ($p = 0; $p < ceil(count($departmentStats) / 6); $p++)
                            <span
                                class="h-1.5 rounded-full transition-all duration-300 cursor-pointer"
                                :class="activeDeptPage === {{ $p }} ? 'w-6 bg-emerald-600 dark:bg-emerald-500' : 'w-1.5 bg-slate-300 dark:bg-slate-700'"
                                @click="activeDeptPage = {{ $p }}"
                            ></span>
                        @endfor
                    </div>
                @endif
            </section>

            <!-- RIGHT PANEL: Jadual Aktiviti & Mesyuarat Hari Ini (5 of 12 cols = ~42%) -->
            <section class="lg:col-span-5 flex flex-col rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 p-4 shadow-xs dark:shadow-lg overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5 mb-3">
                    <div class="flex items-center gap-2">
                        <x-icon name="calendar" class="w-4 h-4 text-sky-600 dark:text-sky-400" />
                        <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Aktiviti & Mesyuarat Hari Ini
                        </h2>
                    </div>
                    <span class="text-xs font-mono font-bold text-sky-600 dark:text-sky-400">
                        {{ $todayEvents->count() }} Dijadualkan
                    </span>
                </div>

                <div class="flex-1 overflow-y-auto space-y-2.5 pr-1">
                    @forelse ($todayEvents as $event)
                        @php
                            $badgeBg = match ($event->jenis) {
                                'MESYUARAT' => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border-purple-300 dark:border-purple-800',
                                'BENGKEL' => 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border-sky-300 dark:border-sky-800',
                                'KURSUS' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-300 dark:border-amber-800',
                                'LAWATAN_TAPAK' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800',
                                default => 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-300 border-slate-300 dark:border-slate-700',
                            };
                        @endphp

                        <div class="p-3.5 rounded-xl border transition-all relative overflow-hidden shadow-2xs {{ $event->is_live ? 'bg-emerald-50/70 dark:bg-emerald-950/20 border-emerald-400 dark:border-emerald-700/80 shadow-md shadow-emerald-950/20' : 'bg-slate-50/80 dark:bg-slate-950/70 border-slate-200 dark:border-slate-800/80' }}">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-2">
                                    @if ($event->is_live)
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-500/20 text-rose-700 dark:text-rose-400 border border-rose-300 dark:border-rose-500/40 text-[10px] font-black uppercase tracking-wider animate-pulse">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Sedang Berlangsung
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-semibold uppercase">
                                            Akan Datang
                                        </span>
                                    @endif

                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md border {{ $badgeBg }}">
                                        {{ $event->jenis_label }}
                                    </span>
                                </div>

                                <span class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">
                                    @if ($event->sepanjang_hari)
                                        Sepanjang Hari
                                    @else
                                        {{ $event->mula->format('H:i') }} - {{ $event->tamat->format('H:i') }}
                                    @endif
                                </span>
                            </div>

                            <h3 class="text-xs font-bold text-slate-900 dark:text-white leading-snug truncate">
                                {{ $event->tajuk }}
                            </h3>

                            <div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-500 dark:text-slate-400 mt-2">
                                @if ($event->lokasi)
                                    <span class="flex items-center gap-1 truncate max-w-[200px]">
                                        <x-icon name="map-pin" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                                        <span>{{ $event->lokasi }}</span>
                                    </span>
                                @endif

                                <span class="flex items-center gap-1 font-mono">
                                    <x-icon name="user-group" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                                    <span>{{ $event->attendees->count() }} Pegawai</span>
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="h-full flex flex-col items-center justify-center p-8 text-center text-slate-400 dark:text-slate-500">
                            <x-icon name="calendar" class="w-10 h-10 mb-2 opacity-30" />
                            <p class="text-xs font-medium">Tiada mesyuarat atau aktiviti dijadualkan hari ini.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </main>

    {{-- ========================================================================= --}}
    {{-- MODE 2: DEPARTMENT & UNIT OPERATIONAL BOARD                               --}}
    {{-- ========================================================================= --}}
    @elseif ($mode === 'department')
        <!-- ZONE 2: DEPARTMENT SUMMARY COUNTERS (4 Cards) -->
        <section class="grid grid-cols-2 md:grid-cols-4 gap-3.5 shrink-0">
            <!-- 1. Department Presence Rate -->
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-xs dark:shadow-md relative overflow-hidden">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    <span>Kehadiran Bahagian</span>
                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/80">
                        KPI: &ge;85%
                    </span>
                </div>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-3xl lg:text-4xl font-black font-mono {{ $deptRate >= 85 ? 'text-emerald-600 dark:text-emerald-400' : ($deptRate >= 70 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
                        {{ number_format($deptRate, 1) }}%
                    </span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 mt-2.5 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500 {{ $deptRate >= 85 ? 'bg-emerald-500' : ($deptRate >= 70 ? 'bg-amber-500' : 'bg-rose-500') }}"
                        style="width: {{ min(100, $deptRate) }}%;"
                    ></div>
                </div>
            </div>

            <!-- 2. Present In Office -->
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-xs dark:shadow-md">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    <span>Hadir Di Pejabat</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                </div>
                <div class="flex items-baseline gap-1.5 mt-1">
                    <span class="text-3xl lg:text-4xl font-black font-mono text-slate-900 dark:text-white">
                        {{ $deptPresent }}
                    </span>
                    <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">
                        / {{ $deptTotal }} orang
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 truncate">
                    {{ $deptUnrecorded }} belum merekodkan
                </p>
            </div>

            <!-- 3. Duty Out -->
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-xs dark:shadow-md">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    <span>Bertugas Luar</span>
                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                </div>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-3xl lg:text-4xl font-black font-mono text-purple-600 dark:text-purple-400">
                        {{ $deptDuty }}
                    </span>
                    <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">orang</span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 truncate">
                    Mesyuarat luar / bengkel / tapak
                </p>
            </div>

            <!-- 4. Leave & MC -->
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-xs dark:shadow-md">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    <span>Cuti & MC</span>
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                </div>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-3xl lg:text-4xl font-black font-mono text-amber-600 dark:text-amber-400">
                        {{ $deptLeave }}
                    </span>
                    <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">orang</span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 truncate">
                    Cuti rehat / cuti perubatan
                </p>
            </div>
        </section>

        <!-- ZONE 3: DEPARTMENT OPERATIONAL SPLIT CANVAS -->
        <main class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-4 min-h-0 overflow-hidden">
            <!-- LEFT PANEL: Senarai Status Anggota Bahagian (8 of 12 cols = ~66%) -->
            <section class="lg:col-span-8 flex flex-col rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 p-4 shadow-xs dark:shadow-lg overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5 mb-3">
                    <div class="flex items-center gap-2">
                        <x-icon name="user-group" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                        <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Senarai Pergerakan & Status Anggota ({{ $currentBahagian->kod ?? '-' }})
                        </h2>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 font-mono">
                        <span>{{ count($staffRoster) }} Anggota Berkhidmat</span>
                    </div>
                </div>

                <!-- 2-Column Personnel Roster Grid (Auto-paginated smoothly via Alpine) -->
                <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-2.5 overflow-hidden content-start">
                    @forelse ($staffRoster as $idx => $officer)
                        <div
                            x-show="Math.floor({{ $idx }} / rosterPerPage) === activeRosterPage"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="p-3 rounded-xl bg-slate-50/80 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-colors"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 font-bold text-xs flex items-center justify-center shrink-0 border border-emerald-300 dark:border-emerald-800">
                                    {{ substr($officer['nama_penuh'], 0, 2) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                        {{ $officer['nama_penuh'] }}
                                    </p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">
                                        {{ $officer['jawatan'] }} &middot; <span class="font-mono text-slate-400">{{ $officer['unit_nama'] }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <span
                                    class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-md border"
                                    style="background-color: {{ $officer['status_warna'] }}20; color: {{ $officer['status_warna'] }}; border-color: {{ $officer['status_warna'] }}40;"
                                >
                                    {{ $officer['status_nama'] }}
                                </span>
                                @if ($officer['masa_masuk'])
                                    <p class="text-[10px] font-mono text-slate-400 mt-0.5">
                                        Masuk: {{ $officer['masa_masuk'] }}
                                    </p>
                                @elseif ($officer['keterangan'])
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate max-w-[110px] mt-0.5" title="{{ $officer['keterangan'] }}">
                                        {{ $officer['keterangan'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-span-2 py-12 text-center text-slate-400 dark:text-slate-500 text-xs">
                            Tiada rekod penempatan anggota bagi bahagian ini.
                        </div>
                    @endforelse
                </div>

                @if (count($staffRoster) > 8)
                    <div class="flex items-center justify-center gap-1.5 pt-2 border-t border-slate-200 dark:border-slate-800/60 mt-2">
                        @for ($rp = 0; $rp < ceil(count($staffRoster) / 8); $rp++)
                            <span
                                class="h-1.5 rounded-full transition-all duration-300 cursor-pointer"
                                :class="activeRosterPage === {{ $rp }} ? 'w-6 bg-emerald-600 dark:bg-emerald-500' : 'w-1.5 bg-slate-300 dark:bg-slate-700'"
                                @click="activeRosterPage = {{ $rp }}"
                            ></span>
                        @endfor
                    </div>
                @endif
            </section>

            <!-- RIGHT PANEL: Pecahan Unit & Jadual Bahagian (4 of 12 cols = ~33%) -->
            <section class="lg:col-span-4 flex flex-col gap-4 overflow-hidden">
                <!-- Pecahan Unit -->
                <div class="flex-1 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 p-4 shadow-xs dark:shadow-lg flex flex-col overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2 mb-2.5">
                        <div class="flex items-center gap-1.5">
                            <x-icon name="organization" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                                Prestasi Mengikut Unit
                            </h3>
                        </div>
                        <span class="text-[11px] font-mono text-slate-400">{{ count($deptUnits) }} Unit</span>
                    </div>

                    <div class="flex-1 overflow-y-auto space-y-2 pr-1">
                        @forelse ($deptUnits as $u)
                            <div class="p-2.5 rounded-xl bg-slate-50/80 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-bold text-slate-800 dark:text-slate-200 truncate max-w-[160px]">
                                        {{ $u['kod'] }} - {{ $u['nama'] }}
                                    </span>
                                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ number_format($u['rate'], 1) }}%
                                    </span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden mb-1">
                                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, $u['rate']) }}%;"></div>
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 font-mono">
                                    <span>{{ $u['present'] }} Hadir</span>
                                    <span>{{ $u['total'] }} Anggota</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-400 text-xs py-4 text-center">Tiada unit bebas direkodkan.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Jadual Bahagian -->
                <div class="h-48 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 p-3.5 shadow-xs dark:shadow-lg flex flex-col overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-1.5 mb-2">
                        <div class="flex items-center gap-1.5">
                            <x-icon name="calendar" class="w-3.5 h-3.5 text-sky-600 dark:text-sky-400" />
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                                Aktiviti Bahagian
                            </h3>
                        </div>
                        <span class="text-[10px] font-mono font-bold text-sky-600 dark:text-sky-400">
                            {{ count($deptEvents) }} Program
                        </span>
                    </div>

                    <div class="flex-1 overflow-y-auto space-y-2 pr-1">
                        @forelse ($deptEvents as $dev)
                            <div class="p-2 rounded-lg bg-slate-50/80 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 text-xs">
                                <div class="flex items-center justify-between font-mono text-[10px] text-slate-500 mb-0.5">
                                    <span class="font-bold text-slate-700 dark:text-slate-300 truncate">{{ $dev->jenis_label }}</span>
                                    <span>{{ $dev->sepanjang_hari ? 'Sepanjang Hari' : $dev->mula->format('H:i') }}</span>
                                </div>
                                <p class="font-semibold text-slate-900 dark:text-white truncate text-xs">{{ $dev->tajuk }}</p>
                            </div>
                        @empty
                            <p class="text-slate-400 text-xs py-4 text-center">Tiada aktiviti hari ini.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </main>

    {{-- ========================================================================= --}}
    {{-- MODE 3: LOBBY & PUBLIC RECEPTION KIOSK (PDPA-Safe)                        --}}
    {{-- ========================================================================= --}}
    @else
        <!-- ZONE 2: PUBLIC GREETING & SERVICE READINESS CARDS -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-4 shrink-0">
            <!-- 1. Office Operating Status -->
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-xs dark:shadow-md flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-emerald-100 dark:bg-emerald-950/80 border border-emerald-300 dark:border-emerald-800/80 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                    <x-icon name="building-office" class="w-7 h-7" />
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status Operasi</span>
                    <h3 class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">
                        BEROPERASI PENUH
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Kaunter dan talian rasmi dibuka</p>
                </div>
            </div>

            <!-- 2. Counter Service Operating Hours -->
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-xs dark:shadow-md flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-sky-100 dark:bg-sky-950/80 border border-sky-300 dark:border-sky-800/80 flex items-center justify-center text-sky-600 dark:text-sky-400 shrink-0">
                    <x-icon name="clock" class="w-7 h-7" />
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Waktu Urusan Kaunter</span>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white mt-0.5 font-mono">
                        08:00 PG - 05:00 PTG
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Isnin hingga Jumaat (Kecuali Cuti Umum)</p>
                </div>
            </div>

            <!-- 3. Overall Service Readiness Rate -->
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-xs dark:shadow-md flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-purple-100 dark:bg-purple-950/80 border border-purple-300 dark:border-purple-800/80 flex items-center justify-center text-purple-600 dark:text-purple-400 shrink-0">
                    <x-icon name="check-badge" class="w-7 h-7" />
                </div>
                <div class="flex-1">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kesiapsiagaan Operasi</span>
                    <h3 class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-0.5 font-mono">
                        {{ number_format($readinessRate, 1) }}%
                    </h3>
                    <div class="w-full h-1.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden mt-1.5">
                        <div class="h-full rounded-full bg-purple-500" style="width: {{ min(100, $readinessRate) }}%;"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ZONE 3: LOBBY PUBLIC CANVAS -->
        <main class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-4 min-h-0 overflow-hidden">
            <!-- LEFT PANEL: Acara Rasmi & Sidang Hari Ini (6 of 12 cols = 50%) -->
            <section class="lg:col-span-6 flex flex-col rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 p-5 shadow-xs dark:shadow-lg overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3 mb-3">
                    <div class="flex items-center gap-2">
                        <x-icon name="calendar" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Acara & Majlis Rasmi Hari Ini
                        </h2>
                    </div>
                    <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400">
                        {{ count($publicEvents) }} Acara
                    </span>
                </div>

                <div class="flex-1 overflow-y-auto space-y-3 pr-1">
                    @forelse ($publicEvents as $pe)
                        <div class="p-4 rounded-xl bg-slate-50/80 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                    {{ $pe->jenis_label }}
                                </span>
                                <span class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">
                                    {{ $pe->sepanjang_hari ? 'Sepanjang Hari' : ($pe->mula->format('H:i') . ' - ' . $pe->tamat->format('H:i')) }}
                                </span>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $pe->tajuk }}</h3>
                            @if ($pe->lokasi)
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
                                    <x-icon name="map-pin" class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                    <span>{{ $pe->lokasi }}</span>
                                </p>
                            @endif
                        </div>
                    @empty
                        <div class="h-full flex flex-col items-center justify-center p-8 text-center text-slate-400 dark:text-slate-500">
                            <x-icon name="calendar" class="w-10 h-10 mb-2 opacity-30" />
                            <p class="text-xs font-medium">Tiada acara rasmi awam dijadualkan hari ini.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <!-- RIGHT PANEL: Makluman Pelawat & Panduan Kaunter (6 of 12 cols = 50%) -->
            <section class="lg:col-span-6 flex flex-col rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 p-5 shadow-xs dark:shadow-lg overflow-hidden justify-between">
                <div>
                    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3 mb-4">
                        <x-icon name="information-circle" class="w-5 h-5 text-sky-600 dark:text-sky-400" />
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Panduan Pelawat & Piagam Pelanggan
                        </h2>
                    </div>

                    <div class="space-y-3.5">
                        <div class="p-3.5 rounded-xl bg-slate-50/80 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 flex items-start gap-3">
                            <span class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">1</span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white">Pendaftaran Keselamatan</h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Sila serahkan dokumen pengenalan diri di Kaunter Pengawal Keselamatan untuk mendapatkan Pas Pelawat Rasmi.</p>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50/80 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 flex items-start gap-3">
                            <span class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">2</span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white">Etika Pakaian Sopan</h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Pelawat dikehendaki berpakaian kemas dan sopan selaras dengan etika urusan di premis kerajaan.</p>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50/80 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 flex items-start gap-3">
                            <span class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">3</span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white">Bilik Menunggu & Ruang Rehat</h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Ruang menunggu selesa disediakan di aras lobi utama dengan capaian Wi-Fi Pelawat dan bahan bacaan rasmi.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 flex items-center justify-between text-xs mt-4">
                    <span class="font-bold text-emerald-900 dark:text-emerald-300">Talian Khidmat Pelanggan Lobi:</span>
                    <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400">03-8888 1234 / Ext. 500</span>
                </div>
            </section>
        </main>
    @endif

    <!-- ZONE 4: RUNNING OPERATION TICKER MARQUEE -->
    <footer class="h-9 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 flex items-center overflow-hidden shrink-0 px-3 shadow-xs">
        <div class="flex items-center gap-1.5 pr-3 mr-3 border-r border-slate-200 dark:border-slate-800 text-[11px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider shrink-0">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
            <span>Makluman</span>
        </div>

        <div class="flex-1 overflow-hidden relative">
            <div class="whitespace-nowrap animate-marquee inline-block text-xs text-slate-600 dark:text-slate-300 font-medium">
                @foreach ($tickerMessages as $msg)
                    <span class="mx-6">&bull; {{ $msg }}</span>
                @endforeach
            </div>
        </div>
    </footer>
</div>
