<x-layouts.app title="Kehadiran Harian">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Kehadiran'],
        ['label' => 'Rekod Harian']
    ]" />

    <div class="space-y-6" x-data="{ selectedIds: [], selectAll: false }">
        <!-- Date Selector & Filters Card -->
        <x-ui.card>
            <form method="GET" action="{{ route('attendance.index') }}" class="flex flex-wrap items-end justify-between gap-4">
                <div class="grid w-full grid-cols-1 gap-3 lg:flex lg:w-auto lg:flex-wrap lg:items-end">
                    <div class="min-w-0">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                            Pilih Tarikh Kehadiran
                        </label>
                        <input
                            type="date"
                            name="tarikh"
                            value="{{ $date }}"
                            onchange="this.form.submit()"
                            class="block w-full min-w-0 rounded-lg border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-900 focus:border-emerald-500 focus:ring-emerald-500 lg:w-auto dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        />
                    </div>

                    @if ($bahagianOptions->isNotEmpty())
                        <div class="min-w-0">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Tapis Bahagian
                            </label>
                            <select
                                name="bahagian_id"
                                onchange="this.form.submit()"
                                class="block w-full min-w-0 rounded-lg border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 lg:w-auto dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            >
                                <option value="">Semua Bahagian</option>
                                @foreach ($bahagianOptions as $b)
                                    <option value="{{ $b->id }}" {{ request('bahagian_id') == $b->id ? 'selected' : '' }}>
                                        {{ $b->kod }} — {{ $b->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($unitOptions->isNotEmpty())
                        <div class="min-w-0">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Tapis Unit
                            </label>
                            <select
                                name="unit_id"
                                onchange="this.form.submit()"
                                class="block w-full min-w-0 rounded-lg border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 lg:w-auto dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            >
                                <option value="">Semua Unit</option>
                                @foreach ($unitOptions as $u)
                                    <option value="{{ $u->id }}" {{ request('unit_id') == $u->id ? 'selected' : '' }}>
                                        {{ $u->kod }} — {{ $u->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <div class="flex w-full flex-wrap items-center gap-2 lg:w-auto lg:justify-end">
                    @include('attendance.partials.whatsapp-modal', ['date' => $date])

                    @if (auth()->user()?->hasPermission('attendance.manage'))
                        <form method="POST" action="{{ route('attendance.prime-defaults') }}" class="inline">
                            @csrf
                            <input type="hidden" name="tarikh" value="{{ $date }}">
                            <x-ui.button
                                type="submit"
                                variant="secondary"
                                size="sm"
                                title="Tetapkan status Pejabat bagi anggota yang belum direkodkan pada tarikh ini"
                            >
                                <x-icon name="check" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                                <span>Jana Pejabat</span>
                            </x-ui.button>
                        </form>
                    @endif

                    <x-ui.button
                        type="button"
                        @click="$dispatch('open-slide-over', { name: 'slideover-create-attendance' })"
                        variant="primary"
                        size="sm"
                    >
                        <x-icon name="plus" class="w-4 h-4" />
                        <span>Catat Rekod Terperinci</span>
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <!-- Batch Check-in Bar (Appears when rows selected) -->
        @if (auth()->user()?->hasPermission('attendance.manage'))
            <div
                x-show="selectedIds.length > 0"
                x-transition
                class="p-4 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 rounded-xl flex flex-wrap items-center justify-between gap-3"
            >
                <div class="flex items-center gap-2">
                    <x-icon name="check" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                    <span class="text-xs font-semibold text-emerald-900 dark:text-emerald-200">
                        <span x-text="selectedIds.length"></span> anggota dipilih untuk kemaskini berkelompok:
                    </span>
                </div>

                <form method="POST" action="{{ route('attendance.batch') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="tarikh" value="{{ $date }}">
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="anggota_ids[]" :value="id">
                    </template>

                    <select name="status_id" class="rounded-lg border-emerald-300 dark:border-emerald-700 bg-white dark:bg-slate-800 text-xs px-3 py-1.5 font-medium">
                        @foreach ($statuses as $st)
                            <option value="{{ $st->id }}">{{ $st->nama }}</option>
                        @endforeach
                    </select>

                    <x-ui.button type="submit" variant="primary" size="sm">
                        Kemas Kini Pilihan
                    </x-ui.button>
                </form>
            </div>
        @endif

        <!-- Attendance Roster Grid -->
        <x-ui.card :title="'Status Kehadiran Tarikh: ' . \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y')" description="Klik butang status untuk kemaskini pantas atau guna ikon pensel untuk butiran">
            <div class="hidden overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800 xl:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            @if (auth()->user()?->hasPermission('attendance.manage'))
                                <th class="w-10 px-4 py-3">
                                    <input
                                        type="checkbox"
                                        x-model="selectAll"
                                        @change="if (selectAll) { selectedIds = {{ json_encode($anggotaList->getCollection()->pluck('id')) }} } else { selectedIds = [] }"
                                        class="rounded-sm border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500"
                                    >
                                </th>
                            @endif
                            <th class="px-4 py-3">Anggota</th>
                            <th class="px-4 py-3">Penempatan</th>
                            <th class="px-4 py-3">Status Semasa</th>
                            <th class="px-4 py-3">Waktu / Catatan</th>
                            @if (auth()->user()?->hasPermission('attendance.manage'))
                                <th class="px-4 py-3 text-center">Tindakan Pantas</th>
                                <th class="px-4 py-3 text-right">Butiran</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($anggotaList as $a)
                            @php
                                $rec = $records->get($a->id);
                                $placement = $a->penempatan->first();
                            @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                @if (auth()->user()?->hasPermission('attendance.manage'))
                                    <td class="px-4 py-3.5">
                                        <input
                                            type="checkbox"
                                            value="{{ $a->id }}"
                                            x-model="selectedIds"
                                            class="rounded-sm border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500"
                                        >
                                    </td>
                                @endif
                                <td class="px-4 py-3.5">
                                    <p class="font-medium text-slate-900 dark:text-white leading-tight">
                                        {{ $a->nama_penuh_gelaran }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                        {{ $a->jawatan ?? 'Anggota' }} &bull; <span class="font-mono">{{ $a->no_pekerja ?? '-' }}</span>
                                    </p>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-600 dark:text-slate-400">
                                    @if ($placement)
                                        <span class="font-medium text-slate-800 dark:text-slate-200">
                                            {{ $placement->lokasi_penempatan }}
                                        </span>
                                    @else
                                        <span class="text-rose-500 italic">Tiada Penempatan</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    @if ($rec)
                                        <x-ui.badge :color="$rec->status->warna">
                                            {{ $rec->status->nama }}
                                        </x-ui.badge>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-md border border-dashed border-slate-300 dark:border-slate-700 text-slate-400">
                                            Belum Direkod
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-500">
                                    @if ($rec)
                                        @if (!$rec->sepanjang_hari && $rec->masa_mula)
                                            <span class="font-mono">{{ $rec->masa_mula }} - {{ $rec->masa_tamat }}</span>
                                        @endif
                                        @if ($rec->lokasi || $rec->catatan)
                                            <p class="truncate max-w-xs text-slate-600 dark:text-slate-300 mt-0.5">
                                                {{ $rec->lokasi ? "Lokasi: {$rec->lokasi}" : $rec->catatan }}
                                            </p>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>

                                @if (auth()->user()?->hasPermission('attendance.manage'))
                                    <!-- Quick 1-Click Status Update Buttons -->
                                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1">
                                            @foreach ($statuses as $st)
                                                <form method="POST" action="{{ route('attendance.store') }}" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="anggota_id" value="{{ $a->id }}">
                                                    <input type="hidden" name="tarikh" value="{{ $date }}">
                                                    <input type="hidden" name="status_id" value="{{ $st->id }}">
                                                    <input type="hidden" name="sepanjang_hari" value="1">
                                                    <button
                                                        type="submit"
                                                        title="Tandakan {{ $st->nama }}"
                                                        class="w-6 h-6 rounded-md text-[10px] font-bold border transition-transform hover:scale-110 flex items-center justify-center cursor-pointer {{ $rec && $rec->status_id == $st->id ? 'ring-2 ring-slate-900 dark:ring-white ring-offset-1' : 'opacity-80 hover:opacity-100' }}"
                                                        style="background-color: {{ $st->warna }}; color: #ffffff; border-color: {{ $st->warna }};"
                                                    >
                                                        {{ substr($st->kod, 0, 1) }}
                                                    </button>
                                                </form>
                                            @endforeach
                                        </div>
                                    </td>

                                    <!-- Detail / Edit Link -->
                                    <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                        @if ($rec)
                                            <x-ui.button href="{{ route('attendance.show', $rec) }}" variant="ghost" size="sm" title="Papar Rekod">
                                                <x-icon name="eye" class="w-4 h-4" />
                                            </x-ui.button>
                                            <x-ui.button href="{{ route('attendance.edit', $rec) }}" variant="ghost" size="sm" title="Kemaskini">
                                                <x-icon name="pencil" class="w-4 h-4 text-emerald-600" />
                                            </x-ui.button>
                                        @else
                                            <x-ui.button href="{{ route('attendance.create', ['anggota_id' => $a->id, 'tarikh' => $date]) }}" variant="ghost" size="sm" title="Catat Rekod">
                                                <x-icon name="plus" class="w-4 h-4 text-emerald-600" />
                                            </x-ui.button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-8 text-center text-slate-400 dark:text-slate-500">
                                    Tiada anggota berkhidmat dijumpai untuk kriteria ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile / tablet roster cards -->
            <div class="space-y-3 xl:hidden">
                @if (auth()->user()?->hasPermission('attendance.manage'))
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2 dark:border-slate-800 dark:bg-slate-800/40">
                        <label class="inline-flex items-center gap-2 text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                            <input
                                type="checkbox"
                                x-model="selectAll"
                                @change="if (selectAll) { selectedIds = {{ json_encode($anggotaList->getCollection()->pluck('id')) }} } else { selectedIds = [] }"
                                class="h-4 w-4 rounded-sm border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800"
                            >
                            Pilih semua (halaman ini)
                        </label>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400" x-text="selectedIds.length + ' dipilih'"></span>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    @forelse ($anggotaList as $a)
                        @php
                            $rec = $records->get($a->id);
                            $placement = $a->penempatan->first();
                        @endphp
                        <x-ui.record-card>
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex min-w-0 items-start gap-2.5">
                                    @if (auth()->user()?->hasPermission('attendance.manage'))
                                        <input
                                            type="checkbox"
                                            value="{{ $a->id }}"
                                            x-model="selectedIds"
                                            class="mt-1 h-4 w-4 shrink-0 rounded-sm border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800"
                                        >
                                    @endif
                                    <div class="min-w-0">
                                        <p class="line-clamp-2 text-sm font-semibold text-slate-900 dark:text-white">
                                            {{ $a->nama_penuh_gelaran }}
                                        </p>
                                        <p class="mt-0.5 truncate text-[11px] text-slate-500 dark:text-slate-400">
                                            {{ $a->jawatan ?? 'Anggota' }} &bull; <span class="font-mono">{{ $a->no_pekerja ?? '-' }}</span>
                                        </p>
                                        <p class="mt-0.5 truncate text-[11px] text-slate-600 dark:text-slate-300">
                                            @if ($placement)
                                                {{ $placement->lokasi_penempatan }}
                                            @else
                                                <span class="italic text-rose-500">Tiada Penempatan</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                @if ($rec)
                                    <x-ui.badge :color="$rec->status->warna" class="shrink-0 whitespace-nowrap">
                                        {{ $rec->status->nama }}
                                    </x-ui.badge>
                                @else
                                    <span class="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-md border border-dashed border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-400 dark:border-slate-700">
                                        Belum Direkod
                                    </span>
                                @endif
                            </div>

                            <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-slate-100 pt-2 text-[11px] text-slate-500 dark:border-slate-800 dark:text-slate-400">
                                @if ($rec)
                                    <span class="inline-flex items-center gap-1">
                                        <x-icon name="clock" class="h-3.5 w-3.5" />
                                        {{ $rec->sepanjang_hari ? 'Sepanjang Hari' : (($rec->masa_mula && $rec->masa_tamat) ? "{$rec->masa_mula} - {$rec->masa_tamat}" : 'Masa tidak dinyatakan') }}
                                    </span>
                                    @if ($rec->lokasi || $rec->catatan)
                                        <span class="inline-flex min-w-0 items-center gap-1">
                                            <x-icon name="map-pin" class="h-3.5 w-3.5 shrink-0" />
                                            <span class="truncate">{{ $rec->lokasi ?? $rec->catatan }}</span>
                                        </span>
                                    @endif
                                @else
                                    <span>Belum ada rekod untuk tarikh ini</span>
                                @endif
                            </div>

                            @if (auth()->user()?->hasPermission('attendance.manage'))
                                <div class="mt-2.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                        Tindakan pantas
                                    </p>
                                    <div class="mt-1.5 grid grid-cols-5 gap-1.5">
                                        @foreach ($statuses as $st)
                                            <form method="POST" action="{{ route('attendance.store') }}" class="contents">
                                                @csrf
                                                <input type="hidden" name="anggota_id" value="{{ $a->id }}">
                                                <input type="hidden" name="tarikh" value="{{ $date }}">
                                                <input type="hidden" name="status_id" value="{{ $st->id }}">
                                                <input type="hidden" name="sepanjang_hari" value="1">
                                                <button
                                                    type="submit"
                                                    title="Tandakan {{ $st->nama }}"
                                                    class="flex h-11 w-full items-center justify-center rounded-lg text-[11px] font-bold text-white transition-transform hover:scale-105 cursor-pointer {{ $rec && $rec->status_id == $st->id ? 'ring-2 ring-slate-900 ring-offset-1 dark:ring-white dark:ring-offset-slate-900' : 'opacity-80 hover:opacity-100' }}"
                                                    style="background-color: {{ $st->warna }}; border-color: {{ $st->warna }};"
                                                >
                                                    {{ substr($st->kod, 0, 1) }}
                                                </button>
                                            </form>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mt-2.5 flex items-center justify-end gap-2">
                                    @if ($rec)
                                        <x-ui.button href="{{ route('attendance.show', $rec) }}" variant="secondary" size="sm">
                                            <x-icon name="eye" class="h-3.5 w-3.5" />
                                            Butiran
                                        </x-ui.button>
                                        <x-ui.button href="{{ route('attendance.edit', $rec) }}" variant="ghost" size="sm">
                                            <x-icon name="pencil" class="h-3.5 w-3.5 text-emerald-600" />
                                            Kemaskini
                                        </x-ui.button>
                                    @else
                                        <x-ui.button href="{{ route('attendance.create', ['anggota_id' => $a->id, 'tarikh' => $date]) }}" variant="secondary" size="sm">
                                            <x-icon name="plus" class="h-3.5 w-3.5 text-emerald-600" />
                                            Catat Rekod
                                        </x-ui.button>
                                    @endif
                                </div>
                            @endif
                        </x-ui.record-card>
                    @empty
                        <x-ui.empty-state message="Tiada anggota berkhidmat dijumpai untuk kriteria ini." class="col-span-full" />
                    @endforelse
                </div>
            </div>

            @if ($anggotaList->hasPages())
                <div class="mt-4">
                    {{ $anggotaList->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>

    @if (auth()->user()?->hasPermission('attendance.manage'))
        <!-- Create Attendance Slide-Over (5-10 fields) -->
        <x-ui.slide-over name="slideover-create-attendance" title="Catat Rekod Kehadiran Terperinci" description="Merekod kehadiran dengan masa pelepasan, waktu bekerja luar, atau perincian cuti">
            <form method="POST" action="{{ route('attendance.store') }}" class="space-y-4">
                @csrf
                @include('attendance.partials.form', [
                    'attendance' => null,
                    'statuses' => $statuses,
                    'anggotaList' => $anggotaList,
                    'selectedAnggotaId' => null,
                    'selectedDate' => $date
                ])
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button type="button" variant="secondary" @click="$dispatch('close-slide-over', { name: 'slideover-create-attendance' })">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Simpan Rekod Kehadiran
                    </x-ui.button>
                </div>
            </form>
        </x-ui.slide-over>
    @endif
</x-layouts.app>
