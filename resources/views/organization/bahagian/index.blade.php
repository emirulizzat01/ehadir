<x-layouts.app title="Senarai Bahagian">
    <div
        class="space-y-6"
        x-data="{ action: '', kod: '', nama: '', aktif: true }"
        x-on:open-edit-bahagian.window="action = $event.detail.action; kod = $event.detail.kod; nama = $event.detail.nama; aktif = $event.detail.aktif; $dispatch('open-modal', { name: 'modal-edit-bahagian' });"
    >
        <x-ui.breadcrumbs :items="[
            ['label' => 'Organisasi'],
            ['label' => 'Bahagian']
        ]" />

    <x-ui.card title="Pengurusan Bahagian" description="Senarai bahagian organisasi dalam sistem">
        <x-slot:actions>
            @if (auth()->user()?->hasPermission('org.manage'))
                <x-ui.button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'modal-create-bahagian' })"
                    variant="primary"
                    size="sm"
                >
                    <x-icon name="plus" class="w-4 h-4" />
                    <span>Tambah Bahagian</span>
                </x-ui.button>
            @endif
        </x-slot:actions>

        <!-- DataTables Toolbar with Search & Entries Selector -->
        <x-datatable.toolbar
            :action="route('organization.bahagian.index')"
            searchPlaceholder="Cari mengikut kod atau nama bahagian..."
            :resetUrl="route('organization.bahagian.index')"
        />

        <!-- Desktop DataTable -->
        <x-datatable.container>
            <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <x-datatable.th column="kod" label="Kod" />
                    <x-datatable.th column="nama" label="Nama Bahagian" />
                    <x-datatable.th column="bil_anggota" label="Bil. Anggota Aktif" />
                    <x-datatable.th column="aktif" label="Status" />
                    <x-datatable.th :sortable="false" label="Tindakan" align="right" />
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($bahagianList as $b)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3.5 font-mono text-xs font-semibold text-slate-700 dark:text-slate-300">
                                {{ $b->kod }}
                            </td>
                            <td class="px-5 py-3.5 font-medium text-slate-900 dark:text-white">
                                <a href="{{ route('organization.bahagian.show', $b) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">
                                    {{ $b->nama }}
                                </a>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600 dark:text-slate-400">
                                {{ $b->aktif_anggota_count }} orang
                            </td>
                            <td class="px-5 py-3.5">
                                <x-ui.badge :variant="$b->aktif ? 'success' : 'default'">
                                    {{ $b->aktif ? 'Aktif' : 'Tidak Aktif' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-5 py-3.5 text-right space-x-1 whitespace-nowrap">
                                <x-ui.button href="{{ route('organization.bahagian.show', $b) }}" variant="ghost" size="sm" title="Papar Maklumat">
                                    <x-icon name="eye" class="w-4 h-4" />
                                </x-ui.button>
                                @if (auth()->user()?->hasPermission('org.manage'))
                                    <button
                                        type="button"
                                        @click="$dispatch('open-edit-bahagian', { action: '{{ route('organization.bahagian.update', $b) }}', kod: '{{ $b->kod }}', nama: '{{ addslashes($b->nama) }}', aktif: {{ $b->aktif ? 'true' : 'false' }} })"
                                        class="p-1.5 text-slate-400 hover:text-emerald-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer inline-flex items-center"
                                        title="Kemaskini"
                                    >
                                        <x-icon name="pencil" class="w-4 h-4 text-emerald-600" />
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-datatable.empty message="Tiada rekod bahagian dijumpai." :resetUrl="route('organization.bahagian.index')" colspan="5" />
                    @endforelse
                </tbody>
            </x-datatable.container>

        <!-- Mobile / tablet bahagian cards -->
        <div class="grid grid-cols-1 gap-3 xl:hidden lg:grid-cols-2">
            @forelse ($bahagianList as $b)
                <x-ui.record-card>
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <a href="{{ route('organization.bahagian.show', $b) }}" class="line-clamp-2 text-sm font-semibold text-slate-900 hover:text-emerald-600 dark:text-white dark:hover:text-emerald-400">
                                {{ $b->nama }}
                            </a>
                            <p class="mt-0.5 font-mono text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                {{ $b->kod }}
                            </p>
                        </div>
                        <x-ui.badge :variant="$b->aktif ? 'success' : 'default'" class="shrink-0 whitespace-nowrap">
                            {{ $b->aktif ? 'Aktif' : 'Tidak Aktif' }}
                        </x-ui.badge>
                    </div>

                    <p class="mt-2 flex items-center gap-1.5 text-[11px] text-slate-600 dark:text-slate-400">
                        <x-icon name="user-group" class="h-3.5 w-3.5 text-slate-400" />
                        {{ $b->aktif_anggota_count }} anggota aktif
                    </p>

                    <div class="mt-2.5 flex items-center justify-end gap-2 border-t border-slate-100 pt-2 dark:border-slate-800">
                        <x-ui.button href="{{ route('organization.bahagian.show', $b) }}" variant="secondary" size="sm">
                            <x-icon name="eye" class="h-3.5 w-3.5" />
                            Papar
                        </x-ui.button>
                        @if (auth()->user()?->hasPermission('org.manage'))
                            <button
                                type="button"
                                @click="$dispatch('open-edit-bahagian', { action: '{{ route('organization.bahagian.update', $b) }}', kod: '{{ $b->kod }}', nama: '{{ addslashes($b->nama) }}', aktif: {{ $b->aktif ? 'true' : 'false' }} })"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-emerald-600 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                <x-icon name="pencil" class="h-3.5 w-3.5 text-emerald-600" />
                                Kemaskini
                            </button>
                        @endif
                    </div>
                </x-ui.record-card>
            @empty
                <x-ui.empty-state message="Tiada rekod bahagian dijumpai." class="col-span-full" />
            @endforelse
        </div>

        <!-- DataTables Footer Counter & Pagination -->
        <x-datatable.footer :paginator="$bahagianList" />
    </x-ui.card>

    @if (auth()->user()?->hasPermission('org.manage'))
        <!-- Create Bahagian Modal (<5 fields) -->
        <x-ui.modal name="modal-create-bahagian" title="Daftar Bahagian Baharu" description="Sila masukkan maklumat bahagian di bawah">
            <form method="POST" action="{{ route('organization.bahagian.store') }}" class="space-y-4">
                @csrf
                @include('organization.bahagian.partials.form', ['bahagian' => null])
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', { name: 'modal-create-bahagian' })">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Simpan Bahagian
                    </x-ui.button>
                </div>
            </form>
        </x-ui.modal>

        <!-- Edit Bahagian Modal (<5 fields) -->
        <x-ui.modal name="modal-edit-bahagian" title="Kemaskini Bahagian" description="Ubah suai butiran bahagian">
            <form method="POST" :action="action" class="space-y-4">
                @csrf
                @method('PUT')
                <x-form.input name="kod" label="Kod Bahagian" x-model="kod" required />
                <x-form.input name="nama" label="Nama Penuh Bahagian" x-model="nama" required />
                <div class="pt-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="aktif" value="1" x-model="aktif" class="rounded border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Status Bahagian Aktif</span>
                            <p class="text-[11px] text-slate-500">Bahagian aktif boleh dipilih untuk penempatan anggota.</p>
                        </div>
                    </label>
                </div>
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', { name: 'modal-edit-bahagian' })">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Kemaskini Bahagian
                    </x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endif
    </div>
</x-layouts.app>
