@props([
    'type' => 'info',
    'dismissible' => true,
])

@php
    $types = [
        'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
        'error' => 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
        'warning' => 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
        'info' => 'bg-sky-50 text-sky-800 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800',
    ];

    $classes = $types[$type] ?? $types['info'];
@endphp

<div x-data="{ show: true }" x-show="show" transition class="flex items-start justify-between p-4 mb-4 text-sm rounded-lg border {{ $classes }}" role="alert">
    <div class="flex items-start gap-3">
        <x-icon :name="$type === 'error' ? 'x-mark' : ($type === 'success' ? 'check' : 'shield')" class="w-5 h-5 shrink-0 mt-0.5" />
        <div>
            {{ $slot }}
        </div>
    </div>
    @if ($dismissible)
        <button type="button" @click="show = false" class="text-current opacity-70 hover:opacity-100 transition-opacity p-1 ml-2">
            <x-icon name="x-mark" class="w-4 h-4" />
        </button>
    @endif
</div>
