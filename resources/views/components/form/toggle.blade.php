@props([
    'name',
    'label' => null,
    'checked' => true,
    'hint' => null,
])

<div x-data="{ state: {{ old($name, $checked) ? 'true' : 'false' }} }" class="flex items-center justify-between">
    @if ($label)
        <div class="mr-3">
            <label for="{{ $name }}" class="text-sm font-medium text-slate-800 dark:text-slate-200 cursor-pointer">
                {{ $label }}
            </label>
            @if ($hint)
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
            @endif
        </div>
    @endif

    <input type="hidden" name="{{ $name }}" :value="state ? 1 : 0">

    <button
        type="button"
        @click="state = !state"
        :class="state ? 'bg-emerald-600' : 'bg-slate-300 dark:bg-slate-700'"
        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
        role="switch"
        :aria-checked="state"
    >
        <span
            :class="state ? 'translate-x-5' : 'translate-x-0'"
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
        ></span>
    </button>
</div>
