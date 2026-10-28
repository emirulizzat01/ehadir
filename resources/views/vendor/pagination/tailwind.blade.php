@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-wrap items-center justify-between gap-2">
        <p class="hidden text-[11px] text-slate-500 sm:block dark:text-slate-400">
            Menunjukkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} daripada {{ $paginator->total() }}
        </p>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-8 items-center rounded-lg border border-slate-200 px-2.5 text-slate-300 dark:border-slate-700 dark:text-slate-600" aria-disabled="true" aria-label="Halaman sebelumnya">
                    <x-icon name="chevron-left" class="h-4 w-4" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-8 items-center rounded-lg border border-slate-200 px-2.5 text-slate-600 transition-colors hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Halaman sebelumnya">
                    <x-icon name="chevron-left" class="h-4 w-4" />
                </a>
            @endif

            <span class="px-2 text-[11px] font-medium text-slate-500 sm:hidden dark:text-slate-400">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            <div class="hidden items-center gap-1 sm:flex">
                @php
                    $start = max(1, $paginator->currentPage() - 1);
                    $end = min($paginator->lastPage(), $paginator->currentPage() + 1);
                @endphp

                @if ($start > 1)
                    <a href="{{ $paginator->url(1) }}" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-slate-200 px-2 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Halaman 1">
                        1
                    </a>
                    @if ($start > 2)
                        <span class="px-1 text-xs text-slate-400" aria-hidden="true">…</span>
                    @endif
                @endif

                @for ($page = $start; $page <= $end; $page++)
                    @if ($page === $paginator->currentPage())
                        <span aria-current="page" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-emerald-600 bg-emerald-600 px-2 text-xs font-semibold text-white dark:border-emerald-500">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $paginator->url($page) }}" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-slate-200 px-2 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Halaman {{ $page }}">
                            {{ $page }}
                        </a>
                    @endif
                @endfor

                @if ($end < $paginator->lastPage())
                    @if ($end < $paginator->lastPage() - 1)
                        <span class="px-1 text-xs text-slate-400" aria-hidden="true">…</span>
                    @endif
                    <a href="{{ $paginator->url($paginator->lastPage()) }}" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-slate-200 px-2 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Halaman {{ $paginator->lastPage() }}">
                        {{ $paginator->lastPage() }}
                    </a>
                @endif
            </div>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-8 items-center rounded-lg border border-slate-200 px-2.5 text-slate-600 transition-colors hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Halaman seterusnya">
                    <x-icon name="chevron-right" class="h-4 w-4" />
                </a>
            @else
                <span class="inline-flex h-8 items-center rounded-lg border border-slate-200 px-2.5 text-slate-300 dark:border-slate-700 dark:text-slate-600" aria-disabled="true" aria-label="Halaman seterusnya">
                    <x-icon name="chevron-right" class="h-4 w-4" />
                </span>
            @endif
        </div>
    </nav>
@endif
