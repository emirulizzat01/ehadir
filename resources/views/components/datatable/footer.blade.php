@props([
    'paginator' => null,
])

@if ($paginator)
    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between text-xs text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
        <!-- DataTables-style Record Counter -->
        <div>
            @if ($paginator->total() > 0)
                <span>Menunjukkan</span>
                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ number_format($paginator->firstItem()) }}</span>
                <span>hingga</span>
                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ number_format($paginator->lastItem()) }}</span>
                <span>daripada</span>
                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ number_format($paginator->total()) }}</span>
                <span>rekod</span>
            @else
                <span>Tiada rekod untuk dipaparkan.</span>
            @endif
        </div>

        <!-- Pagination Links -->
        @if ($paginator->hasPages())
            <div>
                {{ $paginator->links() }}
            </div>
        @endif
    </div>
@endif

