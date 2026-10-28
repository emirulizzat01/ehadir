<x-layouts.app title="Perincian Audit Log">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Pentadbiran Sistem'],
        ['label' => 'Audit Trail', 'url' => route('audit.index')],
        ['label' => 'Log #' . $log->id]
    ]" />

    <div class="max-w-4xl mx-auto space-y-6">
        <x-ui.card :title="'Log Audit #' . $log->id" :description="'Transaksi pada jadual ' . $log->nama_jadual">
            <x-slot:actions>
                <x-ui.button href="{{ route('audit.index') }}" variant="secondary" size="sm">
                    Kembali
                </x-ui.button>
            </x-slot:actions>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Tindakan</p>
                    <div class="mt-1">
                        <x-ui.badge :variant="$log->tindakan === 'DELETE' ? 'danger' : ($log->tindakan === 'CREATE' ? 'success' : 'info')">
                            {{ $log->tindakan }}
                        </x-ui.badge>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Jadual & Rekod</p>
                    <p class="text-xs font-mono font-bold text-slate-900 dark:text-white mt-1">
                        {{ $log->nama_jadual }} (ID: {{ $log->record_id ?? '-' }})
                    </p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Pengguna Pelaksana</p>
                    <p class="text-xs text-slate-900 dark:text-white mt-1">
                        {{ $log->user?->nama ?? 'Sistem / Tetamu' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Waktu Transaksi</p>
                    <p class="text-xs font-mono text-slate-600 dark:text-slate-400 mt-1">
                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                    </p>
                </div>
            </div>

            <!-- JSON Diff Snapshot -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Data Lama -->
                <div>
                    <h4 class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        Data Sebelum Transaksi (data_lama)
                    </h4>
                    <pre class="max-h-96 overflow-y-auto whitespace-pre-wrap break-words rounded-xl bg-slate-900 p-4 font-mono text-xs text-slate-100">{{ $log->data_lama ? json_encode($log->data_lama, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'null (Rekod Baharu)' }}</pre>
                </div>

                <!-- Data Baru -->
                <div>
                    <h4 class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Data Selepas Transaksi (data_baru)
                    </h4>
                    <pre class="max-h-96 overflow-y-auto whitespace-pre-wrap break-words rounded-xl bg-slate-900 p-4 font-mono text-xs text-slate-100">{{ $log->data_baru ? json_encode($log->data_baru, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'null (Rekod Dipadam)' }}</pre>
                </div>
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
