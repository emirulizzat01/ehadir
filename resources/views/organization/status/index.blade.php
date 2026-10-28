<x-layouts.app title="Konfigurasi Status Kehadiran">
    <div
        class="space-y-6"
        x-data="{ action: '', kod: '', nama: '', color: '#64748B', susunan: 1, aktif: true }"
        x-on:open-edit-status.window="action = $event.detail.action; kod = $event.detail.kod; nama = $event.detail.nama; color = $event.detail.warna; susunan = $event.detail.susunan; aktif = $event.detail.aktif; $dispatch('open-slide-over', { name: 'slideover-edit-status' });"
    >
        <x-ui.breadcrumbs :items="[
            ['label' => 'Organisasi'],
            ['label' => 'Status Kehadiran']
        ]" />

    <x-ui.card title="Konfigurasi Status Kehadiran" description="Urus ketetapan kod status, warna lencana (badge) dan susunan keutamaan paparan">
        <x-slot:actions>
            <x-ui.button
                type="button"
                @click="$dispatch('open-slide-over', { name: 'slideover-create-status' })"
                variant="primary"
                size="sm"
            >
                <x-icon name="plus" class="w-4 h-4" />
                <span>Tambah Status</span>
            </x-ui.button>
        </x-slot:actions>

        <div class="hidden overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800 xl:block">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3">Susunan</th>
                        <th class="px-5 py-3">Kod</th>
                        <th class="px-5 py-3">Nama Status</th>
                        <th class="px-5 py-3">Warna Lencana</th>
                        <th class="px-5 py-3">Jumlah Penggunaan Rekod</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($statuses as $st)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3.5 font-bold text-xs text-slate-500">
                                #{{ $st->susunan }}
                            </td>
                            <td class="px-5 py-3.5 font-mono text-xs font-semibold text-slate-800 dark:text-slate-200">
                                {{ $st->kod }}
                            </td>
                            <td class="px-5 py-3.5 font-medium text-slate-900 dark:text-white">
                                {{ $st->nama }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-md border border-black/10 shrink-0" style="background-color: {{ $st->warna }};"></span>
                                    <span class="font-mono text-xs text-slate-600 dark:text-slate-400">{{ $st->warna }}</span>
                                    <x-ui.badge :color="$st->warna">
                                        {{ $st->nama }}
                                    </x-ui.badge>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-400">
                                {{ number_format($st->rekod_kehadiran_count) }} rekod
                            </td>
                            <td class="px-5 py-3.5">
                                <x-ui.badge :variant="$st->aktif ? 'success' : 'default'">
                                    {{ $st->aktif ? 'Aktif' : 'Nyahaktif' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <button
                                    type="button"
                                    @click="$dispatch('open-edit-status', { action: '{{ route('organization.attendance-statuses.update', $st) }}', kod: '{{ $st->kod }}', nama: '{{ addslashes($st->nama) }}', warna: '{{ $st->warna }}', susunan: {{ $st->susunan }}, aktif: {{ $st->aktif ? 'true' : 'false' }} })"
                                    class="p-1.5 text-slate-400 hover:text-emerald-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer inline-flex items-center"
                                    title="Kemaskini"
                                >
                                    <x-icon name="pencil" class="w-4 h-4 text-emerald-600" />
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile / tablet status cards -->
        <div class="grid grid-cols-1 gap-3 xl:hidden lg:grid-cols-2">
            @foreach ($statuses as $st)
                <x-ui.record-card>
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                                {{ $st->nama }}
                            </p>
                            <p class="mt-0.5 font-mono text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                #{{ $st->susunan }} &bull; {{ $st->kod }}
                            </p>
                        </div>
                        <x-ui.badge :variant="$st->aktif ? 'success' : 'default'" class="shrink-0 whitespace-nowrap">
                            {{ $st->aktif ? 'Aktif' : 'Nyahaktif' }}
                        </x-ui.badge>
                    </div>

                    <div class="mt-2.5 flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50/60 p-2 dark:border-slate-800 dark:bg-slate-800/40">
                        <span class="h-5 w-5 shrink-0 rounded-md border border-black/10" style="background-color: {{ $st->warna }};"></span>
                        <span class="font-mono text-[11px] text-slate-600 dark:text-slate-400">{{ $st->warna }}</span>
                        <x-ui.badge :color="$st->warna" class="ml-auto shrink-0 whitespace-nowrap">
                            {{ $st->nama }}
                        </x-ui.badge>
                    </div>

                    <p class="mt-2 text-[11px] text-slate-600 dark:text-slate-400">
                        {{ number_format($st->rekod_kehadiran_count) }} rekod digunakan
                    </p>

                    <div class="mt-2.5 flex justify-end border-t border-slate-100 pt-2 dark:border-slate-800">
                        <button
                            type="button"
                            @click="$dispatch('open-edit-status', { action: '{{ route('organization.attendance-statuses.update', $st) }}', kod: '{{ $st->kod }}', nama: '{{ addslashes($st->nama) }}', warna: '{{ $st->warna }}', susunan: {{ $st->susunan }}, aktif: {{ $st->aktif ? 'true' : 'false' }} })"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-emerald-600 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            <x-icon name="pencil" class="h-3.5 w-3.5 text-emerald-600" />
                            Kemaskini
                        </button>
                    </div>
                </x-ui.record-card>
            @endforeach
        </div>
    </x-ui.card>

    <!-- Create Status Slide-Over (5-10 fields) -->
    <x-ui.slide-over name="slideover-create-status" title="Daftar Status Kehadiran Baharu" description="Sila masukkan kod, label nama dan kod warna HEX">
        <form method="POST" action="{{ route('organization.attendance-statuses.store') }}" class="space-y-4">
            @csrf
            @include('organization.status.partials.form', ['status' => null])
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                <x-ui.button type="button" variant="secondary" @click="$dispatch('close-slide-over', { name: 'slideover-create-status' })">
                    Batal
                </x-ui.button>
                <x-ui.button type="submit" variant="primary">
                    Simpan Status
                </x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <!-- Edit Status Slide-Over (5-10 fields) -->
    <x-ui.slide-over name="slideover-edit-status" title="Kemaskini Status Kehadiran" description="Ubah suai nama paparan, warna lencana dan susunan">
        <form method="POST" :action="action" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.input name="kod" label="Kod Status Unik" x-model="kod" required />
                    <x-form.input name="nama" label="Nama Paparan Status" x-model="nama" required />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Pilih Warna Lencana (HEX) <span class="text-rose-500">*</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="color" x-model="color" class="w-10 h-10 p-0.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 cursor-pointer">
                            <input type="text" name="warna" x-model="color" maxlength="7" class="block w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 font-mono text-sm px-3.5 py-2 text-slate-900 dark:text-slate-100" required>
                        </div>
                    </div>

                    <x-form.input name="susunan" type="number" label="Susunan Keutamaan" x-model="susunan" required />
                </div>

                <div class="p-3.5 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">Pratonton Lencana:</span>
                    <x-ui.badge ::color="color">
                        Contoh Status
                    </x-ui.badge>
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="aktif" value="1" x-model="aktif" class="rounded border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Status Aktif</span>
                            <p class="text-[11px] text-slate-500">Status aktif boleh dipilih dalam borang kehadiran harian.</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                <x-ui.button type="button" variant="secondary" @click="$dispatch('close-slide-over', { name: 'slideover-edit-status' })">
                    Batal
                </x-ui.button>
                <x-ui.button type="submit" variant="primary">
                    Kemaskini Status
                </x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>
    </div>
</x-layouts.app>
