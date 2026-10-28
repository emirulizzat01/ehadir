@props([
    'title' => null,
    'description' => null,
    'actions' => null,
    'padding' => 'p-4 sm:p-6',
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden']) }}>
    @if ($title || $actions)
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div class="min-w-0">
                @if ($title)
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white leading-tight">
                        {{ $title }}
                    </h3>
                @endif
                @if ($description)
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ $description }}
                    </p>
                @endif
            </div>
            @if ($actions)
                <div class="flex flex-wrap items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-6 dark:border-slate-800 dark:bg-slate-800/50">
            {{ $footer }}
        </div>
    @endisset
</div>
