<div
    x-data="{
        showShortcutsModal: false,
        handleShortcut(e) {
            const tag = (e.target.tagName || '').toLowerCase();
            if (['input', 'textarea', 'select'].includes(tag) || e.target.isContentEditable) {
                return;
            }
            if (e.metaKey || e.ctrlKey || e.altKey) {
                return;
            }

            const key = e.key;
            if (key === 't' || key === 'T') {
                e.preventDefault();
                $wire.goToToday();
            } else if (key === 'm' || key === 'M') {
                e.preventDefault();
                $wire.setView('month');
            } else if (key === 'w' || key === 'W') {
                e.preventDefault();
                $wire.setView('week');
            } else if (key === 'd' || key === 'D') {
                e.preventDefault();
                $wire.setView('day');
            } else if (key === 'a' || key === 'A') {
                e.preventDefault();
                $wire.setView('agenda');
            } else if (key === 'j' || key === 'J' || key === 'ArrowLeft') {
                e.preventDefault();
                $wire.previousPeriod();
            } else if (key === 'k' || key === 'K' || key === 'ArrowRight') {
                e.preventDefault();
                $wire.nextPeriod();
            } else if (key === 'c' || key === 'C') {
                e.preventDefault();
                $dispatch('open-slide-over', { name: 'slideover-create-event' });
            } else if (key === '?') {
                e.preventDefault();
                this.showShortcutsModal = !this.showShortcutsModal;
            }
        }
    }"
    @keydown.window="handleShortcut($event)"
    class="space-y-6"
>
    <!-- TOP TOOLBAR (Google Calendar Main Header) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-xs">
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
            <!-- Left Controls: Sidebar toggle + Today + Chevrons + Title -->
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <button
                    type="button"
                    wire:click="toggleSidebar"
                    class="p-2 rounded-xl text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                    title="{{ $showSidebar ? 'Sembunyi Bar Sisi' : 'Papar Bar Sisi' }}"
                >
                    <x-icon name="bars-3" class="w-5 h-5" />
                </button>

                <button
                    type="button"
                    wire:click="goToToday"
                    class="px-3.5 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 font-semibold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-2xs cursor-pointer"
                    title="Lompat ke tarikh hari ini (Kekunci T)"
                >
                    Hari Ini
                </button>

                <!-- Navigation Arrows -->
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        wire:click="previousPeriod"
                        class="p-1.5 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                        title="Tempoh Sebelumnya (Kekunci J atau ←)"
                    >
                        <x-icon name="chevron-left" class="w-4 h-4" />
                    </button>
                    <button
                        type="button"
                        wire:click="nextPeriod"
                        class="p-1.5 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                        title="Tempoh Seterusnya (Kekunci K atau →)"
                    >
                        <x-icon name="chevron-right" class="w-4 h-4" />
                    </button>
                </div>

                <!-- Prominent Current Period Title -->
                <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white ml-1 capitalize tracking-tight">
                    {{ $periodTitle }}
                </h2>

                <!-- Loading indicator -->
                <div wire:loading class="ml-2">
                    <span class="inline-block w-3.5 h-3.5 rounded-full border-2 border-emerald-600 border-t-transparent animate-spin"></span>
                </div>
            </div>

            <!-- Middle: Search Input -->
            <div class="flex-1 max-w-md relative">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <x-icon name="search" class="w-4 h-4" />
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari acara, lokasi atau catatan..."
                        class="w-full pl-9 pr-8 py-1.5 text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-emerald-500 transition-all"
                    >
                    @if ($search !== '')
                        <button
                            type="button"
                            wire:click="$set('search', '')"
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        >
                            <x-icon name="x-mark" class="w-3.5 h-3.5" />
                        </button>
                    @endif
                </div>
            </div>

            <!-- Right: View Switcher (Month, Week, Day, Agenda) & Shortcuts Button -->
            <div class="flex items-center justify-between sm:justify-end gap-2">
                <!-- Segmented Control Button Group -->
                <div class="inline-flex rounded-xl bg-slate-100 dark:bg-slate-800 p-1 border border-slate-200 dark:border-slate-700 text-xs font-semibold">
                    <button
                        type="button"
                        wire:click="setView('month')"
                        class="px-3 py-1 rounded-lg transition-all cursor-pointer {{ $view === 'month' ? 'bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-400 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                        title="Paparan Bulan (Kekunci M)"
                    >
                        Bulan
                    </button>
                    <button
                        type="button"
                        wire:click="setView('week')"
                        class="px-3 py-1 rounded-lg transition-all cursor-pointer {{ $view === 'week' ? 'bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-400 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                        title="Paparan Minggu (Kekunci W)"
                    >
                        Minggu
                    </button>
                    <button
                        type="button"
                        wire:click="setView('day')"
                        class="px-3 py-1 rounded-lg transition-all cursor-pointer {{ $view === 'day' ? 'bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-400 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                        title="Paparan Hari (Kekunci D)"
                    >
                        Hari
                    </button>
                    <button
                        type="button"
                        wire:click="setView('agenda')"
                        class="px-3 py-1 rounded-lg transition-all cursor-pointer {{ $view === 'agenda' ? 'bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-400 font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                        title="Paparan Agenda (Kekunci A)"
                    >
                        Agenda
                    </button>
                </div>

                <!-- Keyboard shortcuts button -->
                <button
                    type="button"
                    @click="showShortcutsModal = true"
                    class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    title="Pintasan Papan Kekunci (?)"
                >
                    <span class="w-5 h-5 rounded-lg border border-slate-300 dark:border-slate-700 flex items-center justify-center text-xs font-mono font-bold">
                        ?
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- MAIN BODY: SIDEBAR + VIEWPORT -->
    <div class="flex flex-col lg:flex-row items-start gap-6">
        <!-- Collapsible Sidebar -->
        @if ($showSidebar)
            @include('livewire.calendar.partials.sidebar')
        @endif

        <!-- Main Calendar Canvas -->
        <div class="flex-1 min-w-0 w-full">
            @if ($view === 'month')
                @include('livewire.calendar.views.month-grid')
            @elseif ($view === 'week')
                @include('livewire.calendar.views.week-grid')
            @elseif ($view === 'day')
                @include('livewire.calendar.views.day-grid')
            @elseif ($view === 'agenda')
                @include('livewire.calendar.views.agenda-list')
            @endif
        </div>
    </div>

    <!-- Google Calendar Quick-View Popover Modal -->
    @include('livewire.calendar.partials.event-popover')

    <!-- Keyboard Shortcuts Modal Dialog -->
    <div
        x-show="showShortcutsModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
        @keydown.escape.window="showShortcutsModal = false"
    >
        <div class="fixed inset-0" @click="showShortcutsModal = false"></div>

        <div class="relative bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full p-6 space-y-4 z-10">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <x-icon name="calendar" class="w-4 h-4 text-emerald-600" />
                    <span>Pintasan Papan Kekunci (Google Calendar)</span>
                </h3>
                <button
                    type="button"
                    @click="showShortcutsModal = false"
                    class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                >
                    <x-icon name="x-mark" class="w-4 h-4" />
                </button>
            </div>

            <div class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300">
                <div class="flex items-center justify-between py-1">
                    <span>Lompat ke Hari Ini</span>
                    <kbd class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-mono font-bold text-[11px]">T</kbd>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span>Tukar ke Paparan Bulan</span>
                    <kbd class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-mono font-bold text-[11px]">M</kbd>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span>Tukar ke Paparan Minggu</span>
                    <kbd class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-mono font-bold text-[11px]">W</kbd>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span>Tukar ke Paparan Hari</span>
                    <kbd class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-mono font-bold text-[11px]">D</kbd>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span>Tukar ke Paparan Agenda</span>
                    <kbd class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-mono font-bold text-[11px]">A</kbd>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span>Tempoh Sebelumnya</span>
                    <div class="flex items-center gap-1">
                        <kbd class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-mono font-bold text-[11px]">J</kbd>
                        <span>atau</span>
                        <kbd class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-mono font-bold text-[11px]">←</kbd>
                    </div>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span>Tempoh Seterusnya</span>
                    <div class="flex items-center gap-1">
                        <kbd class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-mono font-bold text-[11px]">K</kbd>
                        <span>atau</span>
                        <kbd class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-mono font-bold text-[11px]">→</kbd>
                    </div>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span>Cipta / Jadualkan Aktiviti Baharu</span>
                    <kbd class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-mono font-bold text-[11px]">C</kbd>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span>Tutup Popover / Modal</span>
                    <kbd class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-mono font-bold text-[11px]">Esc</kbd>
                </div>
            </div>

            <div class="pt-2 text-center">
                <button
                    type="button"
                    @click="showShortcutsModal = false"
                    class="w-full py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition-colors"
                >
                    Faham & Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Slide-Over: Create Calendar Event (5-10 fields rule) -->
    @if (auth()->user()?->hasPermission('calendar.manage'))
        <x-ui.slide-over name="slideover-create-event" title="Jadualkan Aktiviti Baharu" width="w-screen max-w-2xl">
            <form method="POST" action="{{ route('calendar.events.store') }}" class="space-y-6">
                @csrf

                @include('calendar.partials.form', [
                    'event' => null,
                    'availableAttendees' => $availableAttendees,
                    'selectedAttendeeIds' => old('attendees', []),
                    'defaultDate' => $currentDate,
                    'defaultTime' => '09:00',
                ])

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button type="button" variant="secondary" @click="$dispatch('close-slide-over', { name: 'slideover-create-event' })">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Jadualkan Aktiviti
                    </x-ui.button>
                </div>
            </form>
        </x-ui.slide-over>
    @endif
</div>
