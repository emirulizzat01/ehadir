@props([
    'attendees',
    'selected' => [],
    'name' => 'attendees',
    'label' => 'Pilih Anggota Terlibat (Skop Dibenarkan)',
    'hint' => 'Pilih seorang atau lebih anggota yang berada dalam skop pentadbiran anda:',
    'required' => true,
    'placeholder' => 'Pilih atau cari anggota...',
])

@php
    $selectedIds = collect(old($name, $selected))->map(fn ($id) => (int) $id)->all();
    $attendeeData = $attendees->map(function ($a) {
        return [
            'id' => $a->id,
            'name' => $a->nama_penuh_gelaran,
            'jawatan' => $a->jawatan ?? 'Anggota',
            'no_pekerja' => $a->no_pekerja ?? '',
            'placement' => $a->penempatan->first()?->lokasi_penempatan ?? '',
        ];
    })->values()->all();
@endphp

<div
    x-data="{
        open: false,
        search: '',
        allAttendees: @js($attendeeData),
        selectedIds: @js($selectedIds),

        get selectedAttendees() {
            return this.allAttendees.filter(a => this.selectedIds.includes(a.id));
        },

        get filteredAttendees() {
            if (!this.search.trim()) return this.allAttendees;
            const q = this.search.toLowerCase();
            return this.allAttendees.filter(a =>
                a.name.toLowerCase().includes(q) ||
                a.jawatan.toLowerCase().includes(q) ||
                a.no_pekerja.toLowerCase().includes(q) ||
                a.placement.toLowerCase().includes(q)
            );
        },

        isSelected(id) {
            return this.selectedIds.includes(id);
        },

        toggle(id) {
            if (this.isSelected(id)) {
                this.selectedIds = this.selectedIds.filter(i => i !== id);
            } else {
                this.selectedIds.push(id);
            }
        },

        remove(id) {
            this.selectedIds = this.selectedIds.filter(i => i !== id);
        },

        selectAll() {
            const filteredIds = this.filteredAttendees.map(a => a.id);
            this.selectedIds = [...new Set([...this.selectedIds, ...filteredIds])];
        },

        clearAll() {
            if (this.search.trim()) {
                const filteredIds = this.filteredAttendees.map(a => a.id);
                this.selectedIds = this.selectedIds.filter(id => !filteredIds.includes(id));
            } else {
                this.selectedIds = [];
            }
        }
    }"
    class="relative"
    @click.outside="open = false"
>
    <!-- Label & Hint matching design spec -->
    @if ($label)
        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
            {{ $label }}
            @if ($required)
                <span class="text-rose-500">*</span>
            @endif
        </label>
    @endif

    @if ($hint)
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">
            {{ $hint }}
        </p>
    @endif

    <!-- Main Input Box (Matches single-line input design) -->
    <div
        @click="open = !open; if (open) $nextTick(() => $refs.searchInput?.focus())"
        class="min-h-[44px] w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm px-3.5 py-2 cursor-pointer transition-all hover:border-slate-400 dark:hover:border-slate-600 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 flex flex-wrap items-center gap-1.5 shadow-2xs"
        :class="{ 'border-emerald-500 ring-2 ring-emerald-500/20': open, 'border-rose-400': {{ $errors->has($name) ? 'true' : 'false' }} }"
    >
        <!-- Selected Badges / Chips -->
        <template x-for="item in selectedAttendees" :key="item.id">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold animate-in fade-in zoom-in-95 duration-100">
                <span x-text="item.name"></span>
                <button
                    type="button"
                    @click.stop="remove(item.id)"
                    class="p-0.5 hover:bg-emerald-200 dark:hover:bg-emerald-800 rounded text-emerald-600 dark:text-emerald-400 transition-colors"
                    title="Buang"
                >
                    <x-icon name="x-mark" class="w-3 h-3 stroke-[2.5]" />
                </button>
            </span>
        </template>

        <!-- Empty State Placeholder (Matches clean input box when 0 selected) -->
        <div x-show="selectedIds.length === 0" class="text-xs sm:text-sm text-slate-400 dark:text-slate-500 select-none py-1">
            {{ $placeholder }}
        </div>

        <!-- Trailing Controls: Badge counter & Chevron -->
        <div class="ml-auto flex items-center gap-2 pl-2 shrink-0">
            <template x-if="selectedIds.length > 0">
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300" x-text="selectedIds.length + ' dipilih'"></span>
            </template>
            <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
        </div>
    </div>

    <!-- Floating Dropdown Selector -->
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute left-0 right-0 z-50 mt-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl overflow-hidden"
    >
        <!-- Search bar inside dropdown -->
        <div class="p-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex items-center gap-2">
            <x-icon name="search" class="w-4 h-4 text-slate-400 shrink-0 ml-1" />
            <input
                x-ref="searchInput"
                type="text"
                x-model="search"
                placeholder="Cari nama anggota, no. pekerja atau jawatan..."
                class="w-full bg-transparent border-0 p-1 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-0 focus:outline-hidden"
                @keydown.escape="open = false"
            >
            <template x-if="search">
                <button type="button" @click="search = ''" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <x-icon name="x-mark" class="w-3.5 h-3.5" />
                </button>
            </template>
        </div>

        <!-- Toolbar: Select All / Clear All / Status -->
        <div class="px-3 py-1.5 bg-slate-100/60 dark:bg-slate-800/30 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500">
            <div class="flex items-center gap-3">
                <button type="button" @click="selectAll()" class="font-semibold text-emerald-600 dark:text-emerald-400 hover:underline cursor-pointer">
                    Pilih Semua
                </button>
                <span>&bull;</span>
                <button type="button" @click="clearAll()" class="font-semibold text-rose-600 dark:text-rose-400 hover:underline cursor-pointer">
                    Kosongkan
                </button>
            </div>
            <div>
                <span class="font-bold text-slate-700 dark:text-slate-300" x-text="selectedIds.length"></span> daripada <span x-text="allAttendees.length"></span> dipilih
            </div>
        </div>

        <!-- Options list -->
        <div class="max-h-60 overflow-y-auto p-1.5 space-y-0.5 divide-y divide-slate-100/60 dark:divide-slate-800/60">
            <template x-for="item in filteredAttendees" :key="item.id">
                <div
                    @click="toggle(item.id)"
                    class="flex items-center justify-between p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/70 cursor-pointer transition-colors group"
                    :class="{ 'bg-emerald-50/50 dark:bg-emerald-950/20': isSelected(item.id) }"
                >
                    <div class="flex items-center gap-3 min-w-0 pr-2">
                        <input
                            type="checkbox"
                            :checked="isSelected(item.id)"
                            @click.stop="toggle(item.id)"
                            class="rounded border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 pointer-events-none"
                        >
                        <div class="truncate">
                            <p class="text-xs font-semibold text-slate-900 dark:text-white truncate" x-text="item.name"></p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                <span x-text="item.jawatan"></span>
                                <template x-if="item.no_pekerja">
                                    <span class="font-mono text-[10px]" x-text="' • ' + item.no_pekerja"></span>
                                </template>
                            </p>
                        </div>
                    </div>
                    <template x-if="isSelected(item.id)">
                        <x-icon name="check" class="w-4 h-4 text-emerald-600 shrink-0 stroke-[2.5]" />
                    </template>
                </div>
            </template>

            <template x-if="filteredAttendees.length === 0">
                <div class="p-5 text-center text-xs text-slate-400">
                    Tiada anggota dijumpai sepadan dengan "<span x-text="search"></span>".
                </div>
            </template>
        </div>
    </div>

    <!-- Hidden form inputs for form submission -->
    <template x-for="id in selectedIds" :key="id">
        <input type="hidden" name="{{ $name }}[]" :value="id">
    </template>

    @error($name)
        <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
    @enderror
</div>
