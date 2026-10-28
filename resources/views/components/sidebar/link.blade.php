@props([
    'href',
    'icon',
    'active' => false,
    'iconClass' => '',
])

@php
    $stateClasses = $active
        ? 'bg-emerald-50 font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400'
        : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800';
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'sidebar-nav-link group ' . $stateClasses]) }}
    @if ($active) aria-current="page" @endif
>
    <x-icon :name="$icon" class="w-5 h-5 shrink-0 {{ $iconClass }}" />
    <span class="flex-1 truncate">{{ $slot }}</span>
    @isset($trailing)
        {{ $trailing }}
    @endif
</a>
