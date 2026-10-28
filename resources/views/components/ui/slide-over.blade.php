@props([
    'name',
    'title' => null,
    'description' => null,
    'maxWidth' => 'xl',
])

@php
    $maxWidthClass = match ($maxWidth) {
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        default => 'max-w-xl',
    };
@endphp

<div
    x-data="{ show: false }"
    x-on:open-slide-over.window="if ($event.detail.name === '{{ $name }}') { show = true; }"
    x-on:close-slide-over.window="if ($event.detail.name === '{{ $name }}') { show = false; }"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-hidden"
    aria-labelledby="slide-over-title-{{ $name }}"
    role="dialog"
    aria-modal="true"
>
    <!-- Backdrop -->
    <div
        x-show="show"
        x-transition:enter="ease-in-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in-out duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
        @click="show = false"
    ></div>

    <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
        <div
            x-show="show"
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="w-screen {{ $maxWidthClass }} bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col justify-between"
        >
            <!-- Header -->
            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex items-start justify-between shrink-0 bg-slate-50/50 dark:bg-slate-800/40">
                <div>
                    @if ($title)
                        <h3 class="text-base font-bold text-slate-900 dark:text-white" id="slide-over-title-{{ $name }}">
                            {{ $title }}
                        </h3>
                    @endif
                    @if ($description)
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ $description }}
                        </p>
                    @endif
                </div>
                <button
                    type="button"
                    @click="show = false"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                    aria-label="Tutup"
                >
                    <x-icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>

            <!-- Scrollable Body -->
            <div class="flex-1 overflow-y-auto p-6 space-y-5">
                {{ $slot }}
            </div>

            <!-- Optional Footer -->
            @isset($footer)
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/50 shrink-0">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
