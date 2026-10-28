@props([
    'name',
    'title' => null,
    'description' => null,
    'maxWidth' => 'lg',
])

@php
    $maxWidthClass = match ($maxWidth) {
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        default => 'sm:max-w-lg',
    };
@endphp

<div
    x-data="{ show: false }"
    x-on:open-modal.window="if ($event.detail.name === '{{ $name }}') { show = true; }"
    x-on:close-modal.window="if ($event.detail.name === '{{ $name }}') { show = false; }"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title-{{ $name }}"
    role="dialog"
    aria-modal="true"
>
    <!-- Backdrop -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
        @click="show = false"
    ></div>

    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div
            x-show="show"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full {{ $maxWidthClass }} p-6"
        >
            <!-- Header -->
            <div class="flex items-start justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                <div>
                    @if ($title)
                        <h3 class="text-base font-bold text-slate-900 dark:text-white" id="modal-title-{{ $name }}">
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
                    class="p-1 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                    aria-label="Tutup"
                >
                    <x-icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>

            <!-- Body -->
            <div class="mt-4">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
