@props([
    'message' => 'Tiada rekod sepadan dijumpai.',
    'resetUrl' => null,
    'colspan' => 1,
])

<tr>
    <td colspan="{{ $colspan }}" class="px-5 py-12 text-center">
        <div class="mx-auto flex max-w-xs flex-col items-center justify-center text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500 mb-3">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                {{ $message }}
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Cuba ubah kriteria carian atau set semula tapisan untuk melihat senarai penuh.
            </p>
            @if ($resetUrl)
                <div class="mt-4">
                    <x-ui.button href="{{ $resetUrl }}" variant="secondary" size="sm">
                        Set Semula Carian
                    </x-ui.button>
                </div>
            @endif
        </div>
    </td>
</tr>

