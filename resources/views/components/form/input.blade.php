@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'placeholder' => null,
    'hint' => null,
    'toggleable' => true,
])

@php
    $inputId = $attributes->get('id', $name);
    $isPassword = ($type === 'password');
    $hasToggle = $isPassword && $toggleable;
@endphp

<div>
    @if ($label)
        <label for="{{ $inputId }}" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            {{ $label }}
            @if ($required)
                <span class="text-rose-500">*</span>
            @endif
        </label>
    @endif

    <div
        class="relative rounded-md shadow-xs"
        @if ($hasToggle)
            x-data="{ showPassword: false }"
        @endif
    >
        <input
            @if ($hasToggle)
                :type="showPassword ? 'text' : 'password'"
                type="password"
                data-password-input
            @else
                type="{{ $type }}"
            @endif
            name="{{ $name }}"
            id="{{ $inputId }}"
            value="{{ old($name, $value) }}"
            @if ($required) required @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            {{ $attributes->merge([
                'class' => 'block w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm ' . ($hasToggle ? 'pl-3.5 pr-10' : 'px-3.5') . ' py-2.5 focus:border-emerald-500 focus:ring-emerald-500 dark:focus:border-emerald-400 dark:focus:ring-emerald-400 border transition-colors disabled:bg-slate-100 dark:disabled:bg-slate-900'
            ]) }}
        />

        @if ($hasToggle)
            <button
                type="button"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-hidden focus:text-slate-600 dark:focus:text-slate-200 transition-colors cursor-pointer"
                @click="showPassword = !showPassword"
                :aria-label="showPassword ? 'Sembunyikan kata laluan' : 'Papar kata laluan'"
                :title="showPassword ? 'Sembunyikan kata laluan' : 'Papar kata laluan'"
                aria-controls="{{ $inputId }}"
                data-password-toggle
            >
                <span x-show="!showPassword" data-icon="eye" class="flex items-center">
                    <x-icon name="eye" class="w-4 h-4" />
                </span>
                <span x-show="showPassword" x-cloak data-icon="eye-slash" class="flex items-center">
                    <x-icon name="eye-slash" class="w-4 h-4" />
                </span>
            </button>
        @endif
    </div>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
    @enderror
</div>
