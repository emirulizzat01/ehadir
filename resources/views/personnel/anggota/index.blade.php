<x-layouts.app title="Direktori Anggota">
    <div
        class="space-y-6"
        x-data="{ action: '', nama_penuh: '', no_pekerja: '', gelaran: '', jawatan: '', susunan: 0, aktif: true }"
        x-on:open-edit-anggota.window="action = $event.detail.action; nama_penuh = $event.detail.nama_penuh; no_pekerja = $event.detail.no_pekerja; gelaran = $event.detail.gelaran; jawatan = $event.detail.jawatan; susunan = $event.detail.susunan; aktif = $event.detail.aktif; $dispatch('open-slide-over', { name: 'slideover-edit-anggota' });"
    >
        <x-ui.breadcrumbs :items="[
            ['label' => 'Anggota'],
            ['label' => 'Direktori']
        ]" />

    <x-ui.card title="Direktori Anggota Organisasi" description="Senarai warga kerja dan penempatan semasa diselaraskan daripada OneStack KEJORA">
        <x-slot:actions>
            @if (auth()->user()?->hasPermission('anggota.manage'))
                <form method="POST" action="{{ route('personnel.anggota.sync-onestack') }}" class="inline" onsubmit="return confirm('Segerakkan direktori anggota daripada OneStack sekarang?')">
                    @csrf
                    <x-ui.button type="submit" variant="primary" size="sm">
                        <x-icon name="history" class="w-4 h-4" />
                        <span>Segerakkan OneStack</span>
                    </x-ui.button>
                </form>
                <x-ui.button type="button" @click="$dispatch('open-slide-over', { name: 'slideover-create-anggota' })" variant="secondary" size="sm">
                    <x-icon name="plus" class="w-4 h-4" />
                    <span>Daftar Manual</span>
                </x-ui.button>
            @endif
        </x-slot:actions>

        <!-- OneStack Sync Banner -->
        <div class="mb-5 p-3.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-lg flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 flex items-center justify-center shrink-0">
                    <x-icon name="shield" class="w-4 h-4" />
                </div>
                <div>
                    <span class="font-bold text-slate-800 dark:text-slate-200 block">Sumber Data Induk: OneStack KEJORA</span>
                    <span class="text-slate-500 dark:text-slate-400">Data anggota berfungsi sebagai sumber hanya-baca berpusat bagi modul kehadiran dan perancangan tugas.</span>
                </div>
            </div>
            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400 shrink-0">
                <span class="px-2 py-1 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-medium">
                    {{ $totalOneStackSynced }} Anggota OneStack
                </span>
                <span class="text-slate-400">
                    {{ $latestSync ? 'Kemaskini: ' . \Carbon\Carbon::parse($latestSync)->diffForHumans() : 'Belum disegerakkan' }}
                </span>
            </div>
        </div>

        <!-- DataTables Toolbar with Search & Contextual Filters -->
        <x-datatable.toolbar
            :action="route('personnel.anggota.index')"
            searchPlaceholder="Cari nama, no. pekerja, jawatan..."
            :resetUrl="route('personnel.anggota.index')"
        >
            <div class="w-32">
                <select
                    name="status"
                    onchange="this.form.submit()"
                    class="block w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs px-2.5 py-1.5 text-slate-700 dark:text-slate-200 focus:ring-emerald-500 focus:border-emerald-500 cursor-pointer shadow-xs"
                >
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Tidak Aktif</option>
                </select>
            </div>

            <div class="w-44">
                <select
                    name="bahagian_id"
                    onchange="this.form.submit()"
                    class="block w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs px-2.5 py-1.5 text-slate-700 dark:text-slate-200 focus:ring-emerald-500 focus:border-emerald-500 cursor-pointer shadow-xs"
                >
                    <option value="">Semua Bahagian</option>
                    @foreach ($bahagianList as $b)
                        <option value="{{ $b->id }}" {{ request('bahagian_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->kod }} — {{ $b->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
        </x-datatable.toolbar>

        <!-- Desktop DataTable -->
        <x-datatable.container>
            <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <x-datatable.th column="no_pekerja" label="No. Pekerja" />
                    <x-datatable.th column="nama" label="Nama Anggota" />
                    <x-datatable.th column="jawatan" label="Jawatan" />
                    <x-datatable.th :sortable="false" label="Bahagian / Unit" />
                    <x-datatable.th column="aktif" label="Status" />
                    <x-datatable.th :sortable="false" label="Tindakan" align="right" />
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($anggotaList as $a)
                        @php
                            $activePlacement = $a->penempatan_aktif;
                            $latestPlacement = $activePlacement ? null : $a->penempatan->first();
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3.5 font-mono text-xs font-semibold text-slate-600 dark:text-slate-400">
                                {{ $a->no_pekerja ?? '-' }}
                            </td>
                            <td class="px-5 py-3.5">
                                <a href="{{ route('personnel.anggota.show', $a) }}" class="font-medium text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 block">
                                    {{ $a->nama_penuh_gelaran }}
                                </a>
                                @if ($a->gred)
                                    <span class="inline-block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-0.5">
                                        Gred {{ $a->gred }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-400">
                                <div class="font-medium text-slate-800 dark:text-slate-200">{{ $a->jawatan ?? '-' }}</div>
                                @if ($a->emel)
                                    <div class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $a->emel }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-700 dark:text-slate-300">
                                {{ $activePlacement?->lokasi_penempatan ?? $latestPlacement?->lokasi_penempatan ?? '-' }}
                            </td>
                            <td class="px-5 py-3.5">
                                <x-ui.badge :variant="$a->aktif ? 'success' : 'default'">
                                    {{ $a->aktif ? 'Aktif' : 'Tidak Aktif' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-5 py-3.5 text-right space-x-1 whitespace-nowrap">
                                <x-ui.button href="{{ route('personnel.anggota.show', $a) }}" variant="ghost" size="sm" title="Lihat Profil">
                                    <x-icon name="eye" class="w-4 h-4" />
                                </x-ui.button>
                                @if (auth()->user()?->hasPermission('anggota.manage'))
                                    @php
                                        $editPayload = [
                                            'action' => route('personnel.anggota.update', $a),
                                            'nama_penuh' => $a->nama_penuh,
                                            'no_pekerja' => $a->no_pekerja ?? '',
                                            'gelaran' => $a->gelaran ?? '',
                                            'jawatan' => $a->jawatan ?? '',
                                            'susunan' => (int) $a->susunan,
                                            'aktif' => (bool) $a->aktif,
                                        ];
                                    @endphp
                                    <x-ui.button
                                        type="button"
                                        @click="$dispatch('open-edit-anggota', {{ json_encode($editPayload) }})"
                                        variant="ghost"
                                        size="sm"
                                        title="Kemaskini"
                                    >
                                        <x-icon name="pencil" class="w-4 h-4 text-emerald-600" />
                                    </x-ui.button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-datatable.empty message="Tiada rekod anggota dijumpai dalam skop anda." :resetUrl="route('personnel.anggota.index')" colspan="6" />
                    @endforelse
                </tbody>
            </x-datatable.container>

        <!-- Mobile / tablet member cards -->
        <div class="grid grid-cols-1 gap-3 xl:hidden lg:grid-cols-2">
            @forelse ($anggotaList as $a)
                @php
                    $activePlacement = $a->penempatan_aktif;
                    $latestPlacement = $activePlacement ? null : $a->penempatan->first();
                    $placementName = $activePlacement?->lokasi_penempatan ?? $latestPlacement?->lokasi_penempatan ?? '-';
                @endphp
                <x-ui.record-card>
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <a href="{{ route('personnel.anggota.show', $a) }}" class="line-clamp-2 text-sm font-semibold text-slate-900 hover:text-emerald-600 dark:text-white dark:hover:text-emerald-400">
                                {{ $a->nama_penuh_gelaran }}
                            </a>
                            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                                <span class="font-mono">{{ $a->no_pekerja ?? '-' }}</span>
                                @if ($a->gred)
                                    &bull; Gred {{ $a->gred }}
                                @endif
                            </p>
                        </div>
                        <x-ui.badge :variant="$a->aktif ? 'success' : 'default'" class="shrink-0 whitespace-nowrap">
                            {{ $a->aktif ? 'Aktif' : 'Tidak Aktif' }}
                        </x-ui.badge>
                    </div>

                    <p class="mt-1.5 truncate text-[11px] font-medium text-slate-700 dark:text-slate-300">
                        {{ $a->jawatan ?? '-' }}
                    </p>
                    @if ($a->emel)
                        <p class="mt-0.5 truncate font-mono text-[11px] text-slate-400 dark:text-slate-500">
                            {{ $a->emel }}
                        </p>
                    @endif
                    <p class="mt-1.5 flex min-w-0 items-center gap-1 text-[11px] text-slate-600 dark:text-slate-400">
                        <x-icon name="map-pin" class="h-3.5 w-3.5 shrink-0" />
                        <span class="truncate">{{ $placementName }}</span>
                    </p>

                    <div class="mt-2.5 flex items-center justify-end gap-2 border-t border-slate-100 pt-2 dark:border-slate-800">
                        <x-ui.button href="{{ route('personnel.anggota.show', $a) }}" variant="secondary" size="sm">
                            <x-icon name="eye" class="h-3.5 w-3.5" />
                            Profil
                        </x-ui.button>
                        @if (auth()->user()?->hasPermission('anggota.manage'))
                            @php
                                $editPayload = [
                                    'action' => route('personnel.anggota.update', $a),
                                    'nama_penuh' => $a->nama_penuh,
                                    'no_pekerja' => $a->no_pekerja ?? '',
                                    'gelaran' => $a->gelaran ?? '',
                                    'jawatan' => $a->jawatan ?? '',
                                    'susunan' => (int) $a->susunan,
                                    'aktif' => (bool) $a->aktif,
                                ];
                            @endphp
                            <x-ui.button
                                type="button"
                                @click="$dispatch('open-edit-anggota', {{ json_encode($editPayload) }})"
                                variant="ghost"
                                size="sm"
                            >
                                <x-icon name="pencil" class="h-3.5 w-3.5 text-emerald-600" />
                                Kemaskini
                            </x-ui.button>
                        @endif
                    </div>
                </x-ui.record-card>
            @empty
                <x-ui.empty-state message="Tiada rekod anggota dijumpai dalam skop anda." class="col-span-full" />
            @endforelse
        </div>

        <!-- DataTables Footer Counter & Pagination -->
        <x-datatable.footer :paginator="$anggotaList" />
    </x-ui.card>

    @if (auth()->user()?->hasPermission('anggota.manage'))
        <!-- Create Anggota Slide-Over (5-10 fields) -->
        <x-ui.slide-over name="slideover-create-anggota" title="Daftar Anggota Baharu (Manual)" description="Pendaftaran manual bagi pengecualian atau akaun luar talian" width="w-screen max-w-2xl">
            <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-lg text-xs text-amber-900 dark:text-amber-300">
                <strong>Peringatan Sumber Induk:</strong> Direktori rasmi diselaraskan daripada OneStack KEJORA. Pendaftaran manual ini hanya bertujuan untuk akaun sementara/manual dan berisiko ditimpa sekiranya wujud konflik No. Pekerja di OneStack.
            </div>
            <form method="POST" action="{{ route('personnel.anggota.store') }}" class="space-y-6">
                @csrf
                @include('personnel.anggota.partials.form', [
                    'anggota' => null,
                    'bahagianList' => $bahagianList,
                    'unitList' => $unitList,
                    'includePlacement' => true,
                ])
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button type="button" variant="secondary" @click="$dispatch('close-slide-over', { name: 'slideover-create-anggota' })">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Simpan Anggota
                    </x-ui.button>
                </div>
            </form>
        </x-ui.slide-over>

        <!-- Edit Anggota Slide-Over (5-10 fields) -->
        <x-ui.slide-over name="slideover-edit-anggota" title="Kemaskini Butiran Anggota" description="Ubah suai profil peribadi dan maklumat perjawatan" width="w-screen max-w-2xl">
            <form method="POST" :action="action" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <x-form.input
                        name="nama_penuh"
                        label="Nama Penuh"
                        x-model="nama_penuh"
                        required
                    />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-form.input
                            name="no_pekerja"
                            label="No. Pekerja"
                            x-model="no_pekerja"
                        />

                        <x-form.input
                            name="gelaran"
                            label="Gelaran"
                            x-model="gelaran"
                            placeholder="Contoh: En. / Pn. / Dr."
                        />

                        <x-form.input
                            name="jawatan"
                            label="Jawatan"
                            x-model="jawatan"
                        />

                        <x-form.input
                            name="susunan"
                            type="number"
                            label="Keutamaan Susunan"
                            x-model="susunan"
                        />
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="aktif" value="1" x-model="aktif" class="rounded border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Status Anggota Aktif</span>
                                <p class="text-[11px] text-slate-500">Anggota aktif disenaraikan dalam rekod dan pelaporan kehadiran harian.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button type="button" variant="secondary" @click="$dispatch('close-slide-over', { name: 'slideover-edit-anggota' })">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Kemaskini Anggota
                    </x-ui.button>
                </div>
            </form>
        </x-ui.slide-over>
    @endif
    </div>
</x-layouts.app>
