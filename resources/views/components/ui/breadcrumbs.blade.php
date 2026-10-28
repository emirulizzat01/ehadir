@props([
    'items' => [], // array of ['label' => '...', 'url' => '...']
])

<nav aria-label="Breadcrumb" class="flex items-center text-xs text-slate-500 dark:text-slate-400 mb-3">
    <ol class="inline-flex items-center space-x-1.5 flex-wrap">
        <li class="inline-flex items-center">
            <a href="{{ route('dashboard') }}" class="hover:text-slate-700 dark:hover:text-slate-200 inline-flex items-center gap-1 transition-colors">
                <x-icon name="dashboard" class="w-3.5 h-3.5" />
                <span>Utama</span>
            </a>
        </li>

        @foreach ($items as $item)
            <li class="flex items-center space-x-1.5">
                <x-icon name="chevron-right" class="w-3 h-3 text-slate-400 dark:text-slate-600 shrink-0" />
                @if (!empty($item['url']) && !$loop->last)
                    <a href="{{ $item['url'] }}" class="hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="font-medium text-slate-800 dark:text-slate-200" aria-current="page">
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
