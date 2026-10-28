<x-layouts.app title="Kemaskini Pengguna">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Pentadbiran Sistem'],
        ['label' => 'Pengguna & Skop', 'url' => route('users.index')],
        ['label' => $user->nama, 'url' => route('users.show', $user)],
        ['label' => 'Kemaskini']
    ]" />

    <div class="max-w-3xl mx-auto">
        <x-ui.card :title="'Kemaskini: ' . $user->nama" description="Ubah suai peranan, kata laluan dan penetapan skop kuasa">
            @if ($user->id !== auth()->id())
                <x-slot:actions>
                    <button
                        type="button"
                        class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline cursor-pointer"
                        x-data
                        @click="$dispatch('open-confirm-modal', { name: 'delete-confirm', action: '{{ route('users.destroy', $user) }}' })"
                    >
                        Padam Pengguna
                    </button>
                </x-slot:actions>
            @endif

            <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Maklumat Akaun -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-3">
                        1. Maklumat Akaun
                    </h4>

                    <div
                        class="space-y-4"
                        x-data="{
                            nama: {{ json_encode(old('nama', $user->nama)) }},
                            email: {{ json_encode(old('email', $user->email)) }},
                            isLinked: {{ old('anggota_id', $user->anggota_id) ? 'true' : 'false' }},
                            onAnggotaSelected(item) {
                                if (item && item.title) {
                                    this.nama = item.title;
                                    if (item.email && !this.email) {
                                        this.email = item.email;
                                    }
                                    this.isLinked = true;
                                }
                            },
                            onAnggotaCleared() {
                                this.isLinked = false;
                            }
                        }"
                        @item-selected="onAnggotaSelected($event.detail)"
                        @item-cleared="onAnggotaCleared()"
                    >
                        <x-form.searchable-select
                            name="anggota_id"
                            label="Pautkan Rekod Anggota (Pilihan)"
                            :endpoint="route('lookup.anggota')"
                            :value="old('anggota_id', $user->anggota_id)"
                            placeholder="Cari nama, no. pekerja, atau jawatan..."
                            hint="Pilih profil anggota daripada Direktori Anggota untuk membolehkan pewarisan skop bahagian/unit dan semakan kehadiran."
                        />

                        <div>
                            <x-form.input
                                name="nama"
                                label="Nama Penuh Pengguna"
                                :value="$user->nama"
                                x-model="nama"
                                required
                            />
                            <template x-if="isLinked">
                                <p class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1 font-medium">
                                    <x-icon name="check" class="w-3.5 h-3.5 inline shrink-0" />
                                    Nama diselaraskan secara automatik daripada profil anggota OneStack terpilih. Anda masih boleh menyunting jika perlu.
                                </p>
                            </template>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-form.input
                                name="email"
                                type="email"
                                label="Alamat Emel Rasmi"
                                :value="$user->email"
                                x-model="email"
                                required
                            />

                            <x-form.input
                                name="password"
                                type="password"
                                label="Kata Laluan Baharu (Pilihan)"
                                placeholder="Biarkan kosong jika tidak ubah"
                            />
                        </div>

                        <div class="pt-2">
                            <x-form.toggle
                                name="aktif"
                                label="Akaun Aktif"
                                :checked="$user->aktif"
                            />
                        </div>
                    </div>
                </div>

                <!-- Peranan Sistem -->
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">
                        2. Peranan Sistem (Roles) <span class="text-rose-500">*</span>
                    </h4>

                    <div class="space-y-2">
                        @foreach ($roles as $r)
                            <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="role_ids[]"
                                    value="{{ $r->id }}"
                                    {{ in_array($r->id, old('role_ids', $selectedRoleIds)) ? 'checked' : '' }}
                                    class="mt-1 rounded-sm border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500"
                                >
                                <div>
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white">
                                        {{ $r->nama }}
                                    </span>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $r->kod === 'SUPERADMIN' ? 'Akses penuh tanpa sekatan kepada semua modul, organisasi, pengguna dan audit log.' : 'Akses terhad kepada modul kehadiran dan anggota mengikut skop yang ditetapkan di bawah.' }}
                                    </p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Skop Kuasa Pentadbiran -->
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                        3. Skop Kuasa Pentadbiran (Bagi Peranan Admin)
                    </h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                        Pilih bahagian atau unit yang boleh diuruskan oleh pengguna ini:
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Bahagian -->
                        <div>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-2">
                                Skop Bahagian:
                            </span>
                            <div class="max-h-44 overflow-y-auto border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 space-y-1.5">
                                @foreach ($bahagianList as $b)
                                    <label class="flex items-center gap-2 p-1 text-xs hover:bg-slate-50 dark:hover:bg-slate-800 rounded-md cursor-pointer">
                                        <input
                                            type="checkbox"
                                            name="scopes_bahagian[]"
                                            value="{{ $b->id }}"
                                            {{ in_array($b->id, old('scopes_bahagian', $selectedBahagianIds)) ? 'checked' : '' }}
                                            class="rounded-sm border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500"
                                        >
                                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ $b->nama }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Unit -->
                        <div>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-2">
                                Skop Unit:
                            </span>
                            <div class="max-h-44 overflow-y-auto border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 space-y-1.5">
                                @foreach ($unitList as $u)
                                    <label class="flex items-center gap-2 p-1 text-xs hover:bg-slate-50 dark:hover:bg-slate-800 rounded-md cursor-pointer">
                                        <input
                                            type="checkbox"
                                            name="scopes_unit[]"
                                            value="{{ $u->id }}"
                                            {{ in_array($u->id, old('scopes_unit', $selectedUnitIds)) ? 'checked' : '' }}
                                            class="rounded-sm border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500"
                                        >
                                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ $u->nama }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <x-ui.button href="{{ route('users.show', $user) }}" variant="secondary">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Kemaskini Pengguna
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
