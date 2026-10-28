@props([
    'name',
    'label' => null,
    'value' => null,
    'placeholder' => 'Pilih atau cari rekod...',
    'endpoint' => null,
    'options' => [],
    'required' => false,
    'hint' => null,
    'allowClear' => true,
    'selectedItem' => null,
])

@php
    $initialValue = old($name, $value);
    
    // Auto-resolve initial selected item if not explicitly provided
    $resolvedSelectedItem = $selectedItem;
    if (! $resolvedSelectedItem && $initialValue) {
        if (is_iterable($options) && count($options) > 0) {
            $found = collect($options)->first(fn ($o) => (is_object($o) ? $o->id : ($o['id'] ?? null)) == $initialValue);
            if ($found) {
                $resolvedSelectedItem = is_object($found) ? [
                    'id' => $found->id,
                    'title' => $found->nama_penuh_gelaran ?? $found->nama ?? $found->name ?? (string) $found->id,
                    'subtitle' => $found->jawatan ?? $found->email ?? null,
                    'badge' => $found->no_pekerja ?? null,
                ] : $found;
            }
        }

        if (! $resolvedSelectedItem && class_exists(\App\Models\Anggota::class)) {
            $foundAnggota = \App\Models\Anggota::with('penempatan.bahagian', 'penempatan.unit')->find($initialValue);
            if ($foundAnggota) {
                $activePlacement = $foundAnggota->penempatanSemasa();
                $placementName = $activePlacement ? $activePlacement->lokasi_penempatan : null;
                $resolvedSelectedItem = [
                    'id' => $foundAnggota->id,
                    'title' => $foundAnggota->nama_penuh_gelaran,
                    'subtitle' => ($foundAnggota->jawatan ? $foundAnggota->jawatan . ($foundAnggota->gred ? ' (' . $foundAnggota->gred . ')' : '') : '') . ($placementName ? ' • ' . $placementName : ''),
                    'badge' => $foundAnggota->no_pekerja ?: null,
                    'department' => $placementName,
                ];
            }
        }
    }

    $initialOptions = is_iterable($options) ? collect($options)->map(function ($opt) {
        if (is_array($opt)) return $opt;
        if (is_object($opt)) {
            return [
                'id' => $opt->id,
                'title' => $opt->nama_penuh_gelaran ?? $opt->nama ?? $opt->name ?? (string) $opt->id,
                'subtitle' => $opt->jawatan ?? $opt->email ?? null,
                'badge' => $opt->no_pekerja ?? null,
                'department' => method_exists($opt, 'penempatanSemasa') ? $opt->penempatanSemasa()?->nama_penempatan : null,
            ];
        }
        return ['id' => $opt, 'title' => (string) $opt];
    })->values()->all() : [];
@endphp

<div
    class="relative"
    x-data="{
        open: false,
        search: '',
        loading: false,
        selectedId: @js($initialValue),
        selectedItem: @js($resolvedSelectedItem),
        results: @js($initialOptions),
        endpoint: @js($endpoint),
        highlightedIndex: -1,

        init() {
            if (this.selectedId && !this.selectedItem) {
                const found = this.results.find(r => String(r.id) === String(this.selectedId));
                if (found) {
                    this.selectedItem = found;
                } else if (this.endpoint) {
                    this.fetchItemById(this.selectedId);
                }
            }
        },

        fetchItemById(id) {
            this.loading = true;
            fetch(`${this.endpoint}?selected_id=${encodeURIComponent(id)}`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.results && data.results.length > 0) {
                    this.selectedItem = data.results[0];
                }
            })
            .catch(() => {})
            .finally(() => { this.loading = false; });
        },

        fetchResults() {
            if (!this.endpoint) {
                return;
            }
            this.loading = true;
            const url = `${this.endpoint}?q=${encodeURIComponent(this.search)}${this.selectedId ? `&selected_id=${this.selectedId}` : ''}`;
            fetch(url, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                this.results = data.results || [];
                this.highlightedIndex = this.results.length > 0 ? 0 : -1;
            })
            .catch(() => {})
            .finally(() => { this.loading = false; });
        },

        onSearchInput() {
            if (this.endpoint) {
                this.fetchResults();
            }
        },

        toggle() {
            if (this.open) {
                this.close();
            } else {
                this.open = true;
                this.highlightedIndex = -1;
                this.$nextTick(() => {
                    this.$refs.searchInput.focus();
                    if (this.endpoint && this.results.length === 0) {
                        this.fetchResults();
                    }
                });
            }
        },

        close() {
            this.open = false;
            this.search = '';
            this.highlightedIndex = -1;
        },

        select(item) {
            this.selectedId = item.id;
            this.selectedItem = item;
            this.close();
            this.$dispatch('input', item.id);
            this.$dispatch('change', item.id);
            this.$dispatch('item-selected', item);
        },

        clear() {
            this.selectedId = null;
            this.selectedItem = null;
            this.search = '';
            this.close();
            this.$dispatch('input', null);
            this.$dispatch('change', null);
            this.$dispatch('item-cleared');
        },

        get displayResults() {
            if (this.endpoint) {
                return this.results;
            }
            if (!this.search.trim()) {
                return this.results;
            }
            const q = this.search.toLowerCase();
            return this.results.filter(r =>
                (r.title && r.title.toLowerCase().includes(q)) ||
                (r.subtitle && r.subtitle.toLowerCase().includes(q)) ||
                (r.badge && r.badge.toLowerCase().includes(q)) ||
                (r.department && r.department.toLowerCase().includes(q))
            );
        },

        navigate(direction) {
            const total = this.displayResults.length;
            if (total === 0) return;
            if (direction === 'down') {
                this.highlightedIndex = (this.highlightedIndex + 1) % total;
            } else if (direction === 'up') {
                this.highlightedIndex = (this.highlightedIndex - 1 + total) % total;
            }
        },

        selectHighlighted() {
            if (this.highlightedIndex >= 0 && this.highlightedIndex < this.displayResults.length) {
                this.select(this.displayResults[this.highlightedIndex]);
            }
        }
    }"
    @click.outside="close()"
    @keydown.escape.prevent="close()"
>
    <!-- Hidden input for form submission -->
    <input type="hidden" name="{{ $name }}" :value="selectedId ?? ''" @if ($required) required @endif>

    @if ($label)
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            {{ $label }}
            @if ($required)
                <span class="text-rose-500">*</span>
            @endif
        </label>
    @endif

    <!-- Trigger Button / Selected State Display -->
    <div
        @click="toggle()"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="toggle()"
        @keydown.space.prevent="toggle()"
        @keydown.arrow-down.prevent="if(!open) toggle()"
        class="w-full flex items-center justify-between rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer min-h-[44px] transition-colors shadow-xs"
        :class="{ 'border-emerald-500 ring-2 ring-emerald-500/20': open }"
    >
        <template x-if="selectedItem">
            <div class="flex items-center gap-2 flex-1 min-w-0 pr-2">
                <template x-if="selectedItem.badge">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-mono font-semibold bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 shrink-0" x-text="selectedItem.badge">
                        {{ $resolvedSelectedItem['badge'] ?? '' }}
                    </span>
                </template>
                <div class="truncate text-left">
                    <span class="font-medium text-slate-900 dark:text-white" x-text="selectedItem.title">
                        {{ $resolvedSelectedItem['title'] ?? '' }}
                    </span>
                    <template x-if="selectedItem.subtitle">
                        <span class="text-xs text-slate-500 dark:text-slate-400 ml-1.5" x-text="'— ' + selectedItem.subtitle">
                            {{ !empty($resolvedSelectedItem['subtitle']) ? '— ' . $resolvedSelectedItem['subtitle'] : '' }}
                        </span>
                    </template>
                </div>
            </div>
        </template>

        <template x-if="!selectedItem">
            <span class="text-slate-400 dark:text-slate-500" x-text="loading ? 'Memuatkan maklumat...' : '{{ $placeholder }}'">
                {{ $placeholder }}
            </span>
        </template>

        <div class="flex items-center gap-1 shrink-0 text-slate-400">
            @if ($allowClear)
                <template x-if="selectedItem">
                    <button
                        type="button"
                        @click.stop="clear()"
                        class="p-1 hover:text-rose-500 hover:bg-slate-100 dark:hover:bg-slate-700 rounded transition-colors"
                        title="Kosongkan pilihan"
                    >
                        <x-icon name="x-mark" class="w-3.5 h-3.5" />
                    </button>
                </template>
            @endif
            <x-icon name="chevron-down" class="w-4 h-4 transition-transform duration-200" ::class="{ 'rotate-180 text-emerald-600': open }" />
        </div>
    </div>

    <!-- Dropdown Panel -->
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 mt-1.5 w-full bg-white dark:bg-slate-800 rounded-lg shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden"
    >
        <!-- Search Input -->
        <div class="p-2 border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-800/80">
            <div class="relative flex items-center">
                <span class="absolute left-3 text-slate-400 pointer-events-none">
                    <x-icon name="search" class="w-3.5 h-3.5" />
                </span>
                <input
                    type="text"
                    x-ref="searchInput"
                    x-model="search"
                    @input.debounce.250ms="onSearchInput()"
                    @keydown.arrow-down.prevent="navigate('down')"
                    @keydown.arrow-up.prevent="navigate('up')"
                    @keydown.enter.prevent="selectHighlighted()"
                    placeholder="Taip untuk mencari..."
                    class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md text-xs py-2 pl-8.5 pr-8 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                >
                <template x-if="loading">
                    <span class="absolute right-2.5">
                        <svg class="animate-spin h-3.5 w-3.5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </template>
            </div>
        </div>

        <!-- Options List -->
        <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700/40 py-1">
            <template x-for="(item, index) in displayResults" :key="item.id">
                <div
                    @click="select(item)"
                    @mouseenter="highlightedIndex = index"
                    class="px-3.5 py-2.5 text-xs cursor-pointer transition-colors flex items-start justify-between gap-3"
                    :class="{
                        'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200': index === highlightedIndex,
                        'text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50': index !== highlightedIndex
                    }"
                >
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-slate-900 dark:text-white" x-text="item.title"></div>
                        <template x-if="item.subtitle">
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate" x-text="item.subtitle"></div>
                        </template>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <template x-if="item.badge">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300" x-text="item.badge"></span>
                        </template>
                        <template x-if="selectedId && String(selectedId) === String(item.id)">
                            <x-icon name="check" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                        </template>
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <template x-if="!loading && displayResults.length === 0">
                <div class="px-4 py-6 text-center text-xs text-slate-400 dark:text-slate-500">
                    <p>Tiada rekod dijumpai.</p>
                </div>
            </template>
        </div>
    </div>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
    @enderror
</div>

