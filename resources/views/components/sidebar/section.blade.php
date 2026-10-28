<div {{ $attributes->merge(['class' => 'sidebar-section']) }}>
    <span class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider dark:text-slate-500">
        {{ $slot }}
    </span>
</div>
