<x-layouts.app title="Senarai Unit">
    <div
        class="space-y-6"
        x-data="{ action: '', kod: '', nama: '', aktif: true }"
        x-on:open-edit-unit.window="action = $event.detail.action; kod = $event.detail.kod; nama = $event.detail.nama; aktif = $event.detail.aktif; $dispatch('open-modal', { name: 'modal-edit-unit' });"
    >
        <x-ui.breadcrumbs :items="[
            ['label' => 'Organisasi'],
            ['label' => 'Unit']
        ]" />

    <x-ui.card title="Pengurusan Unit" description="Senarai unit bebas/mandiri dalam organisasi">
        <x-slot:actions>
            @if (auth()->user()?->hasPermission('org.manage'))
                <x-ui.button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'modal-create-unit' })"
                    variant="primary"
                    size="sm"
                >
                    <x-icon name="plus" class="w-4 h-4" />
                    <span>Tambah Unit</span>
                </x-ui.button>
            @endif
        </x-slot:actions>

        <!-- Search Bar -->
        <form method="GET" action="{{ route('organization.unit.index') }}" class="mb-4 flex items-center gap-3">
            <div class="relative flex-1 max-w-sm">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari mengikut kod atau nama..."
                    class="block w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3.5 py-2 pl-9 text-slate-900 dark:text-slate-100 focus:border-emerald-500 focus:ring-emerald-500"
                />
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <x-icon name="search" class="w-4 h-4" />
                </div>
            </div>
            <x-ui.button type="submit" variant="secondary" size="sm">
                Cari
            </x-ui.button>
            @if (request('search'))
                <x-ui.button href="{{ route('organization.unit.index') }}" variant="ghost" size="sm">
                    Reset
                </x-ui.button>
            @endif
        </form>

        <!-- Table -->
        <div class="hidden overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800 xl:block">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3">Kod</th>
                        <th class="px-5 py-3">Nama Unit</th>
                        <th class="px-5 py-3">Bil. Anggota Aktif</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($unitList as $u)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3.5 font-mono text-xs font-semibold text-slate-700 dark:text-slate-300">
                                {{ $u->kod }}
                            </td>
                            <td class="px-5 py-3.5 font-medium text-slate-900 dark:text-white">
                                <a href="{{ route('organization.unit.show', $u) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">
                                    {{ $u->nama }}
                                </a>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600 dark:text-slate-400">
                                {{ $u->aktif_anggota_count }} orang
                            </td>
                            <td class="px-5 py-3.5">
                                <x-ui.badge :variant="$u->aktif ? 'success' : 'default'">
                                    {{ $u->aktif ? 'Aktif' : 'Tidak Aktif' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-5 py-3.5 text-right space-x-1 whitespace-nowrap">
                                <x-ui.button href="{{ route('organization.unit.show', $u) }}" variant="ghost" size="sm" title="Papar Maklumat">
                                    <x-icon name="eye" class="w-4 h-4" />
                                </x-ui.button>
                                @if (auth()->user()?->hasPermission('org.manage'))
                                    <button
                                        type="button"
                                        @click="$dispatch('open-edit-unit', { action: '{{ route('organization.unit.update', $u) }}', kod: '{{ $u->kod }}', nama: '{{ addslashes($u->nama) }}', aktif: {{ $u->aktif ? 'true' : 'false' }} })"
                                        class="p-1.5 text-slate-400 hover:text-emerald-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer inline-flex items-center"
                                        title="Kemaskini"
                                    >
                                        <x-icon name="pencil" class="w-4 h-4 text-emerald-600" />
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-400 dark:text-slate-500">
                                Tiada rekod unit dijumpai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile / tablet unit cards -->
        <div class="grid grid-cols-1 gap-3 xl:hidden lg:grid-cols-2">
            @forelse ($unitList as $u)
                <x-ui.record-card>
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <a href="{{ route('organization.unit.show', $u) }}" class="line-clamp-2 text-sm font-semibold text-slate-900 hover:text-emerald-600 dark:text-white dark:hover:text-emerald-400">
                                {{ $u->nama }}
                            </a>
                            <p class="mt-0.5 font-mono text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                {{ $u->kod }}
                            </p>
                        </div>
                        <x-ui.badge :variant="$u->aktif ? 'success' : 'default'" class="shrink-0 whitespace-nowrap">
                            {{ $u->aktif ? 'Aktif' : 'Tidak Aktif' }}
                        </x-ui.badge>
                    </div>

                    <p class="mt-2 flex items-center gap-1.5 text-[11px] text-slate-600 dark:text-slate-400">
                        <x-icon name="user-group" class="h-3.5 w-3.5 text-slate-400" />
                        {{ $u->aktif_anggota_count }} anggota aktif
                    </p>

                    <div class="mt-2.5 flex items-center justify-end gap-2 border-t border-slate-100 pt-2 dark:border-slate-800">
                        <x-ui.button href="{{ route('organization.unit.show', $u) }}" variant="secondary" size="sm">
                            <x-icon name="eye" class="h-3.5 w-3.5" />
                            Papar
                        </x-ui.button>
                        @if (auth()->user()?->hasPermission('org.manage'))
                            <button
                                type="button"
                                @click="$dispatch('open-edit-unit', { action: '{{ route('organization.unit.update', $u) }}', kod: '{{ $u->kod }}', nama: '{{ addslashes($u->nama) }}', aktif: {{ $u->aktif ? 'true' : 'false' }} })"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-emerald-600 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                <x-icon name="pencil" class="h-3.5 w-3.5 text-emerald-600" />
                                Kemaskini
                            </button>
                        @endif
                    </div>
                </x-ui.record-card>
            @empty
                <x-ui.empty-state message="Tiada rekod unit dijumpai." class="col-span-full" />
            @endforelse
        </div>

        @if ($unitList->hasPages())
            <div class="mt-4">
                {{ $unitList->links() }}
            </div>
        @endif
    </x-ui.card>

    @if (auth()->user()?->hasPermission('org.manage'))
        <!-- Create Unit Modal (<5 fields) -->
        <x-ui.modal name="modal-create-unit" title="Daftar Unit Baharu" description="Sila masukkan maklumat unit di bawah">
            <form method="POST" action="{{ route('organization.unit.store') }}" class="space-y-4">
                @csrf
                @include('organization.unit.partials.form', ['unit' => null])
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', { name: 'modal-create-unit' })">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Simpan Unit
                    </x-ui.button>
                </div>
            </form>
        </x-ui.modal>

        <!-- Edit Unit Modal (<5 fields) -->
        <x-ui.modal name="modal-edit-unit" title="Kemaskini Unit" description="Ubah suai butiran unit">
            <form method="POST" :action="action" class="space-y-4">
                @csrf
                @method('PUT')
                <x-form.input name="kod" label="Kod Unit" x-model="kod" required />
                <x-form.input name="nama" label="Nama Penuh Unit" x-model="nama" required />
                <div class="pt-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="aktif" value="1" x-model="aktif" class="rounded border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Status Unit Aktif</span>
                            <p class="text-[11px] text-slate-500">Unit aktif boleh dipilih untuk penempatan anggota.</p>
                        </div>
                    </label>
                </div>
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', { name: 'modal-edit-unit' })">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Kemaskini Unit
                    </x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endif
    </div>
</x-layouts.app>
