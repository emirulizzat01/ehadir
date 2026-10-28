@props([
    'name',
    'title' => 'Pengesahan Pemadaman',
    'content' => 'Adakah anda pasti ingin memadam rekod ini? Tindakan ini tidak boleh dibatalkan.',
    'confirmText' => 'Ya, Padam',
    'cancelText' => 'Batal',
])

<div
    x-data="{ show: false, actionUrl: '' }"
    x-on:open-confirm-modal.window="if ($event.detail.name === '{{ $name }}') { actionUrl = $event.detail.action; show = true; }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title"
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
            class="relative transform overflow-hidden rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md p-6"
        >
            <div class="flex items-start gap-4">
                <div class="mx-auto flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 sm:mx-0">
                    <x-icon name="trash" class="h-5 w-5" />
                </div>
                <div class="mt-0 text-left">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white" id="modal-title">
                        {{ $title }}
                    </h3>
                    <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        {{ $content }}
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-row-reverse gap-2 sm:gap-3">
                <form :action="actionUrl" method="POST">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm">
                        {{ $confirmText }}
                    </x-ui.button>
                </form>
                <x-ui.button type="button" variant="secondary" size="sm" @click="show = false">
                    {{ $cancelText }}
                </x-ui.button>
            </div>
        </div>
    </div>
</div>
