@props([
    'variant' => 'default',
    'color' => null, // e.g. '#16A34A'
])

@php
    $baseClasses = 'inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-md border';

    $variants = [
        'default' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
        'danger' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
        'warning' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
        'info' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800',
        'purple' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800',
    ];

    $style = '';
    if ($color) {
        $classes = 'text-slate-800 dark:text-slate-100 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 shadow-xs';
    } else {
        $classes = $variants[$variant] ?? $variants['default'];
    }
@endphp

<span {{ $attributes->merge(['class' => "$baseClasses $classes"]) }}>
    @if ($color)
        <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $color }};"></span>
    @endif
    {{ $slot }}
</span>
