<x-layouts.app :title="$user->nama">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Pentadbiran Sistem'],
        ['label' => 'Pengguna & Skop', 'url' => route('users.index')],
        ['label' => $user->nama]
    ]" />

    <div class="max-w-3xl mx-auto space-y-6">
        <x-ui.card :title="$user->nama" :description="$user->email">
            <x-slot:actions>
                <x-ui.button href="{{ route('users.edit', $user) }}" variant="secondary" size="sm">
                    <x-icon name="pencil" class="w-4 h-4 text-emerald-600" />
                    <span>Kemaskini</span>
                </x-ui.button>
            </x-slot:actions>

            <div class="space-y-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase">Status Akaun</p>
                        <div class="mt-1">
                            <x-ui.badge :variant="$user->aktif ? 'success' : 'default'">
                                {{ $user->aktif ? 'Aktif' : 'Disekat' }}
                            </x-ui.badge>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase">Peranan Ditugaskan</p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            @foreach ($user->roles as $r)
                                <x-ui.badge :variant="$r->kod === 'SUPERADMIN' ? 'purple' : 'info'">
                                    {{ $r->nama }}
                                </x-ui.badge>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase">Log Masuk Terakhir</p>
                        <p class="text-xs text-slate-700 dark:text-slate-300 mt-1 font-mono">
                            {{ $user->last_login ? $user->last_login->format('d/m/Y H:i:s') : 'Belum pernah log masuk' }}
                        </p>
                    </div>
                </div>

                <!-- Linked Anggota Profile -->
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-3">
                        Pautan Rekod Anggota Organisasi
                    </h4>

                    @if ($user->anggota)
                        <div class="p-4 rounded-lg border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/50 dark:bg-emerald-950/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-700 dark:text-emerald-300 shrink-0">
                                    <x-icon name="anggota" class="w-5 h-5" />
                                </div>
                                <div>
                                    <h5 class="text-sm font-semibold text-slate-900 dark:text-white">
                                        {{ $user->anggota->nama_penuh_gelaran }}
                                    </h5>
                                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                                        <span class="font-mono font-medium">{{ $user->anggota->no_pekerja }}</span> &bull; {{ $user->anggota->jawatan }}
                                    </p>
                                    @if ($placement = $user->anggota->penempatanSemasa())
                                        <div class="mt-1.5 flex items-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-400 font-medium">
                                            <x-icon name="organization" class="w-3.5 h-3.5" />
                                            <span>Penempatan: {{ $placement->nama_penempatan }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <x-ui.button href="{{ route('personnel.anggota.show', $user->anggota) }}" variant="secondary" size="sm" class="shrink-0">
                                <span>Lihat Direktori</span>
                                <x-icon name="chevron-right" class="w-4 h-4 ml-1" />
                            </x-ui.button>
                        </div>
                    @else
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-lg border border-slate-200 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400">
                            Tiada rekod anggota dipautkan kepada akaun pengguna ini. Akaun ini beroperasi sebagai pengguna sistem bebas.
                        </div>
                    @endif
                </div>

                <!-- Assigned Scopes Details -->
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Skop Pentadbiran Dibenarkan
                    </h4>

                    @if ($user->isSuperAdmin())
                        <div class="p-3 bg-purple-50 dark:bg-purple-950/40 rounded-lg border border-purple-200 dark:border-purple-800 text-xs text-purple-900 dark:text-purple-300">
                            <strong>Akses Menyeluruh Superadmin</strong> &bull; Pengguna ini mempunyai akses mutlak ke semua bahagian dan unit dalam organisasi tanpa had skop.
                        </div>
                    @elseif ($user->scopes->isNotEmpty())
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($user->scopes as $s)
                                <div class="p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 text-xs flex items-center gap-2">
                                    <x-icon name="organization" class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span class="font-medium text-slate-900 dark:text-white">
                                        {{ $s->scope_name }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @elseif ($user->anggota && ($inherited = $user->anggota->penempatanSemasa()))
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 rounded-lg border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-900 dark:text-emerald-300">
                            <strong>Skop Diwarisi Automatik</strong> &bull; Tiada skop eksplisit dikonfigurasi. Pengguna ini mewarisi akses pentadbiran bagi <strong>{{ $inherited->nama_penempatan }}</strong> secara automatik berdasarkan penempatan aktif anggota.
                        </div>
                    @else
                        <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-lg border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-300">
                            <strong>Tiada Skop Diberikan</strong> &bull; Pengguna ini tidak mempunyai kuasa capaian kepada mana-mana rekod anggota.
                        </div>
                    @endif
                </div>
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
