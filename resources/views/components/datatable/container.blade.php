<div {{ $attributes->merge(['class' => 'hidden overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800 xl:block shadow-2xs']) }}>
    <table class="w-full text-left text-sm divide-y divide-slate-200 dark:divide-slate-800">
        {{ $slot }}
    </table>
</div>

