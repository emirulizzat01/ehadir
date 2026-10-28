<x-layouts.app title="Audit Trail">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Pentadbiran Sistem'],
        ['label' => 'Audit Trail']
    ]" />

    <x-ui.card title="Log Audit & Jejak Keselamatan" description="Rekod transaksi pangkalan data dan aktiviti pengguna mengikut ketetapan Invariant 6">
        <!-- DataTables Toolbar with Search & Contextual Audit Filters -->
        <x-datatable.toolbar
            :action="route('audit.index')"
            searchPlaceholder="Cari jadual, tindakan, ID rekod..."
            :resetUrl="route('audit.index')"
        >
            <div class="w-36">
                <select name="nama_jadual" onchange="this.form.submit()" class="block w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs px-2.5 py-1.5 text-slate-700 dark:text-slate-200 focus:ring-emerald-500 focus:border-emerald-500 cursor-pointer shadow-xs">
                    <option value="">Semua Jadual</option>
                    @foreach ($tableOptions as $t)
                        <option value="{{ $t }}" {{ request('nama_jadual') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-32">
                <select name="tindakan" onchange="this.form.submit()" class="block w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs px-2.5 py-1.5 text-slate-700 dark:text-slate-200 focus:ring-emerald-500 focus:border-emerald-500 cursor-pointer shadow-xs">
                    <option value="">Semua Tindakan</option>
                    @foreach ($actionOptions as $a)
                        <option value="{{ $a }}" {{ request('tindakan') === $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-36">
                <select name="user_id" onchange="this.form.submit()" class="block w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs px-2.5 py-1.5 text-slate-700 dark:text-slate-200 focus:ring-emerald-500 focus:border-emerald-500 cursor-pointer shadow-xs">
                    <option value="">Semua Pengguna</option>
                    @foreach ($userOptions as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-36">
                <input
                    type="date"
                    name="tarikh"
                    value="{{ request('tarikh') }}"
                    onchange="this.form.submit()"
                    class="block w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs px-2.5 py-1.5 text-slate-700 dark:text-slate-200 focus:ring-emerald-500 focus:border-emerald-500 cursor-pointer shadow-xs"
                />
            </div>
        </x-datatable.toolbar>

        <!-- Desktop DataTable -->
        <x-datatable.container>
            <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <x-datatable.th column="masa" label="Masa Transaksi" />
                    <x-datatable.th :sortable="false" label="Pengguna" />
                    <x-datatable.th column="tindakan" label="Tindakan" />
                    <x-datatable.th column="jadual" label="Nama Jadual" />
                    <x-datatable.th column="record_id" label="ID Rekod" />
                    <x-datatable.th :sortable="false" label="Butiran Snapshot" align="right" />
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                            <td class="px-4 py-3 font-mono text-slate-500">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">
                                {{ $log->user?->nama ?? 'Sistem / Guest' }}
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$log->tindakan === 'DELETE' ? 'danger' : ($log->tindakan === 'CREATE' ? 'success' : 'info')">
                                    {{ $log->tindakan }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-700 dark:text-slate-300">
                                {{ $log->nama_jadual }}
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-500">
                                #{{ $log->record_id ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('audit.show', $log) }}" class="font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                                    Papar JSON &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <x-datatable.empty message="Tiada log audit dijumpai bagi kriteria ini." :resetUrl="route('audit.index')" colspan="6" />
                    @endforelse
                </tbody>
            </x-datatable.container>

        <!-- Mobile / tablet collapsible audit rows -->
        <div class="space-y-2 xl:hidden">
            @forelse ($logs as $log)
                <details class="group rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                    <summary class="flex cursor-pointer list-none items-start gap-2 p-3 [&::-webkit-details-marker]:hidden">
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </p>
                            <p class="mt-0.5 line-clamp-2 text-xs font-semibold text-slate-900 dark:text-white">
                                {{ $log->user?->nama ?? 'Sistem / Guest' }}
                            </p>
                            <div class="mt-1.5 flex min-w-0 items-center gap-2">
                                <x-ui.badge :variant="$log->tindakan === 'DELETE' ? 'danger' : ($log->tindakan === 'CREATE' ? 'success' : 'info')" class="shrink-0 whitespace-nowrap">
                                    {{ $log->tindakan }}
                                </x-ui.badge>
                                <span class="min-w-0 truncate font-mono text-[10px] text-slate-400 dark:text-slate-500">
                                    {{ $log->nama_jadual }} &bull; #{{ $log->record_id ?? '-' }}
                                </span>
                            </div>
                        </div>
                        <x-icon name="chevron-down" class="mt-1 h-4 w-4 shrink-0 text-slate-400 transition-transform group-open:rotate-180" />
                    </summary>

                    <div class="border-t border-slate-100 p-3 dark:border-slate-800">
                        <dl class="space-y-1.5 text-[11px]">
                            <div class="flex items-baseline justify-between gap-3">
                                <dt class="text-slate-500 dark:text-slate-400">Jadual</dt>
                                <dd class="font-mono text-slate-800 dark:text-slate-200">{{ $log->nama_jadual }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-3">
                                <dt class="text-slate-500 dark:text-slate-400">ID Rekod</dt>
                                <dd class="font-mono text-slate-800 dark:text-slate-200">#{{ $log->record_id ?? '-' }}</dd>
                            </div>
                        </dl>
                        <div class="mt-2.5 flex justify-end">
                            <x-ui.button href="{{ route('audit.show', $log) }}" variant="secondary" size="sm">
                                <x-icon name="eye" class="h-3.5 w-3.5" />
                                Papar JSON
                            </x-ui.button>
                        </div>
                    </div>
                </details>
            @empty
                <x-ui.empty-state message="Tiada log audit dijumpai bagi kriteria ini." />
            @endforelse
        </div>

        <!-- DataTables Footer Counter & Pagination -->
        <x-datatable.footer :paginator="$logs" />
    </x-ui.card>
</x-layouts.app>
