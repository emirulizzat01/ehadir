@props([
    'title',
    'value',
    'description' => null,
    'icon' => null,
    'color' => 'emerald',
])

@php
    $colorClasses = [
        'emerald' => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-400',
        'blue' => 'text-blue-600 bg-blue-50 dark:bg-blue-950/40 dark:text-blue-400',
        'amber' => 'text-amber-600 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-400',
        'rose' => 'text-rose-600 bg-rose-50 dark:bg-rose-950/40 dark:text-rose-400',
        'purple' => 'text-purple-600 bg-purple-50 dark:bg-purple-950/40 dark:text-purple-400',
        'slate' => 'text-slate-600 bg-slate-100 dark:bg-slate-800 dark:text-slate-400',
    ];
    $iconBg = $colorClasses[$color] ?? $colorClasses['slate'];
@endphp

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex items-center justify-between gap-4">
    <div class="min-w-0">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">
            {{ $title }}
        </p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
            {{ $value }}
        </p>
        @if ($description)
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                {{ $description }}
            </p>
        @endif
    </div>
    @if ($icon)
        <div class="p-3 rounded-lg shrink-0 {{ $iconBg }}">
            <x-icon :name="$icon" class="w-6 h-6" />
        </div>
    @endif
</div>
