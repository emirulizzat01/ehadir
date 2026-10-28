@props([
    'action' => '',
    'search' => true,
    'searchPlaceholder' => 'Cari rekod...',
    'perPage' => true,
    'perPageOptions' => [10, 25, 50, 100],
    'resetUrl' => null,
])

@php
    $currentPerPage = (int) request('per_page', 15);
    $currentSearch = request('search', '');
    $hasFilters = request()->hasAny(['search', 'bahagian_id', 'unit_id', 'status', 'tarikh', 'status_id', 'tindakan', 'nama_jadual', 'user_id']);
@endphp

<form method="GET" action="{{ $action ?: url()->current() }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between py-1 mb-4">
    <!-- Preserved hidden inputs (sorting state) -->
    @if (request('sort'))
        <input type="hidden" name="sort" value="{{ request('sort') }}">
    @endif
    @if (request('direction'))
        <input type="hidden" name="direction" value="{{ request('direction') }}">
    @endif

    <!-- Left Controls: Entries Per Page & Extra Filters Slot -->
    <div class="flex flex-wrap items-center gap-3">
        @if ($perPage)
            <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                <span>Papar</span>
                <select
                    name="per_page"
                    onchange="this.form.submit()"
                    class="rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs px-2.5 py-1.5 font-medium text-slate-800 dark:text-slate-200 focus:ring-emerald-500 focus:border-emerald-500 shadow-xs cursor-pointer"
                >
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" {{ $currentPerPage === $option ? 'selected' : '' }}>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>
                <span>entri</span>
            </div>
        @endif

        @if (!$slot->isEmpty())
            <div class="flex flex-wrap items-center gap-2">
                {{ $slot }}
            </div>
        @endif
    </div>

    <!-- Right Controls: Global Search & Reset -->
    @if ($search)
        <div class="flex items-center gap-2">
            <div class="relative w-full sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    type="text"
                    name="search"
                    value="{{ $currentSearch }}"
                    placeholder="{{ $searchPlaceholder }}"
                    class="block w-full pl-9 pr-8 py-1.5 rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-emerald-500 focus:border-emerald-500 shadow-xs"
                />
                @if ($currentSearch)
                    <a
                        href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => 1]) }}"
                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        title="Padam carian"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @endif
            </div>

            <x-ui.button type="submit" variant="secondary" size="sm" class="shrink-0">
                Cari
            </x-ui.button>

            @if ($resetUrl && ($currentSearch || $hasFilters))
                <x-ui.button href="{{ $resetUrl }}" variant="ghost" size="sm" class="shrink-0" title="Set semula semua tapisan">
                    Set Semula
                </x-ui.button>
            @endif
        </div>
    @endif
</form>

