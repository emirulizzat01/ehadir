<x-layouts.app title="Pengurusan Pengguna">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Pentadbiran Sistem'],
        ['label' => 'Pengguna & Skop']
    ]" />

    <x-ui.card title="Pengurusan Pengguna & Skop Pentadbiran" description="Urus akaun kakitangan pentadbir, penetapan peranan dan hak capaian skop bahagian/unit">
        <x-slot:actions>
            <x-ui.button href="{{ route('users.create') }}" variant="primary" size="sm">
                <x-icon name="plus" class="w-4 h-4" />
                <span>Tambah Pengguna</span>
            </x-ui.button>
        </x-slot:actions>

        <!-- Search Bar -->
        <form method="GET" action="{{ route('users.index') }}" class="mb-4 flex items-center gap-3">
            <div class="relative flex-1 max-w-sm">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari mengikut nama atau emel..."
                    class="block w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3.5 py-2 pl-9 text-slate-900 dark:text-slate-100 focus:border-emerald-500 focus:ring-emerald-500"
                />
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <x-icon name="search" class="w-4 h-4" />
                </div>
            </div>
            <x-ui.button type="submit" variant="secondary" size="sm">
                Cari
            </x-ui.button>
        </form>

        <div class="hidden overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800 xl:block">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3">Nama Pengguna</th>
                        <th class="px-5 py-3">Emel</th>
                        <th class="px-5 py-3">Peranan</th>
                        <th class="px-5 py-3">Skop Akses Pentadbiran</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($users as $u)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3.5 font-medium text-slate-900 dark:text-white">
                                <a href="{{ route('users.show', $u) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 font-semibold block">
                                    {{ $u->nama }}
                                </a>
                                @if ($u->anggota)
                                    <div class="inline-flex items-center gap-1.5 text-xs font-normal text-slate-500 dark:text-slate-400 mt-0.5">
                                        <x-icon name="anggota" class="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                                        <span>{{ $u->anggota->no_pekerja }} &bull; {{ Str::limit($u->anggota->jawatan, 30) }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500 dark:text-slate-400 font-mono">
                                {{ $u->email }}
                            </td>
                            <td class="px-5 py-3.5">
                                @foreach ($u->roles as $r)
                                    <x-ui.badge :variant="$r->kod === 'SUPERADMIN' ? 'purple' : 'info'">
                                        {{ $r->nama }}
                                    </x-ui.badge>
                                @endforeach
                            </td>
                            <td class="px-5 py-3.5 text-xs">
                                @if ($u->isSuperAdmin())
                                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Semua Skop (Superadmin)</span>
                                @elseif ($u->scopes->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        @foreach ($u->scopes as $sc)
                                            <span class="px-2 py-0.5 rounded-sm bg-slate-100 dark:bg-slate-800 text-[11px] font-medium text-slate-700 dark:text-slate-300">
                                                {{ $sc->scope_name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @elseif ($u->anggota && ($placement = $u->anggota->penempatanSemasa()))
                                    <span class="px-2 py-0.5 rounded-sm bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-[11px] font-medium text-emerald-700 dark:text-emerald-300" title="Diwarisi daripada penempatan aktif anggota">
                                        {{ $placement->nama_penempatan }} (Diwarisi)
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">Tiada skop ditetapkan</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <x-ui.badge :variant="$u->aktif ? 'success' : 'default'">
                                    {{ $u->aktif ? 'Aktif' : 'Disekat' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-5 py-3.5 text-right space-x-1 whitespace-nowrap">
                                <x-ui.button href="{{ route('users.show', $u) }}" variant="ghost" size="sm" title="Lihat Profil">
                                    <x-icon name="eye" class="w-4 h-4" />
                                </x-ui.button>
                                <x-ui.button href="{{ route('users.edit', $u) }}" variant="ghost" size="sm" title="Kemaskini">
                                    <x-icon name="pencil" class="w-4 h-4 text-emerald-600" />
                                </x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-400 dark:text-slate-500">
                                Tiada pengguna dijumpai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile / tablet user cards -->
        <div class="grid grid-cols-1 gap-3 xl:hidden lg:grid-cols-2">
            @forelse ($users as $u)
                <x-ui.record-card>
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <a href="{{ route('users.show', $u) }}" class="line-clamp-2 text-sm font-semibold text-slate-900 hover:text-emerald-600 dark:text-white dark:hover:text-emerald-400">
                                {{ $u->nama }}
                            </a>
                            <p class="mt-0.5 truncate font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                {{ $u->email }}
                            </p>
                            @if ($u->anggota)
                                <p class="mt-0.5 truncate text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ $u->anggota->no_pekerja }} &bull; {{ Str::limit($u->anggota->jawatan, 30) }}
                                </p>
                            @endif
                        </div>
                        <x-ui.badge :variant="$u->aktif ? 'success' : 'default'" class="shrink-0 whitespace-nowrap">
                            {{ $u->aktif ? 'Aktif' : 'Disekat' }}
                        </x-ui.badge>
                    </div>

                    <div class="mt-2.5 flex flex-wrap items-center gap-1">
                        @foreach ($u->roles as $r)
                            <x-ui.badge :variant="$r->kod === 'SUPERADMIN' ? 'purple' : 'info'">
                                {{ $r->nama }}
                            </x-ui.badge>
                        @endforeach
                    </div>

                    <div class="mt-2 text-[11px]">
                        @if ($u->isSuperAdmin())
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">Semua Skop (Superadmin)</span>
                        @elseif ($u->scopes->isNotEmpty())
                            <div class="flex flex-wrap gap-1">
                                @foreach ($u->scopes as $sc)
                                    <span class="rounded-sm bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $sc->scope_name }}
                                    </span>
                                @endforeach
                            </div>
                        @elseif ($u->anggota && ($placement = $u->anggota->penempatanSemasa()))
                            <span class="rounded-sm border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300" title="Diwarisi daripada penempatan aktif anggota">
                                {{ $placement->nama_penempatan }} (Diwarisi)
                            </span>
                        @else
                            <span class="italic text-slate-400">Tiada skop ditetapkan</span>
                        @endif
                    </div>

                    <div class="mt-2.5 flex items-center justify-end gap-2 border-t border-slate-100 pt-2 dark:border-slate-800">
                        <x-ui.button href="{{ route('users.show', $u) }}" variant="secondary" size="sm">
                            <x-icon name="eye" class="h-3.5 w-3.5" />
                            Profil
                        </x-ui.button>
                        <x-ui.button href="{{ route('users.edit', $u) }}" variant="ghost" size="sm">
                            <x-icon name="pencil" class="h-3.5 w-3.5 text-emerald-600" />
                            Kemaskini
                        </x-ui.button>
                    </div>
                </x-ui.record-card>
            @empty
                <x-ui.empty-state message="Tiada pengguna dijumpai." class="col-span-full" />
            @endforelse
        </div>

        @if ($users->hasPages())
            <div class="mt-4">
                {{ $users->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
