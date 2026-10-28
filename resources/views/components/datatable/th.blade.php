@props([
    'column' => null,
    'label' => '',
    'sortable' => true,
    'align' => 'left',
])

@php
    $isSortable = $sortable && !empty($column);
    $currentSort = request('sort');
    $currentDir = strtolower(request('direction', 'asc'));
    $isActive = $isSortable && ($currentSort === $column);
    $nextDir = ($isActive && $currentDir === 'asc') ? 'desc' : 'asc';

    $alignmentClass = match ($align) {
        'center' => 'text-center justify-center',
        'right' => 'text-right justify-end',
        default => 'text-left justify-start',
    };

    $headerUrl = $isSortable ? request()->fullUrlWithQuery([
        'sort' => $column,
        'direction' => $nextDir,
        'page' => 1,
    ]) : null;
@endphp

<th scope="col" {{ $attributes->merge(['class' => 'px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800']) }}>
    @if ($isSortable)
        <a
            href="{{ $headerUrl }}"
            class="group inline-flex items-center gap-1.5 font-semibold transition-colors hover:text-emerald-600 dark:hover:text-emerald-400 cursor-pointer select-none {{ $alignmentClass }} {{ $isActive ? 'text-emerald-600 dark:text-emerald-400' : '' }}"
            title="Susun mengikut {{ $label }}"
        >
            <span>{{ $label ?: $slot }}</span>
            <span class="inline-flex shrink-0">
                @if ($isActive && $currentDir === 'asc')
                    <!-- Ascending active arrow -->
                    <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z" clip-rule="evenodd" transform="rotate(180 10 10)" />
                    </svg>
                @elseif ($isActive && $currentDir === 'desc')
                    <!-- Descending active arrow -->
                    <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z" clip-rule="evenodd" />
                    </svg>
                @else
                    <!-- Inactive neutral dual arrows -->
                    <svg class="h-3.5 w-3.5 text-slate-300 dark:text-slate-600 group-hover:text-slate-400 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                    </svg>
                @endif
            </span>
        </a>
    @else
        <div class="inline-flex items-center {{ $alignmentClass }}">
            <span>{{ $label ?: $slot }}</span>
        </div>
    @endif
</th>

