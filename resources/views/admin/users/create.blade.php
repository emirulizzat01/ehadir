<x-layouts.app title="Tambah Pengguna">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Pentadbiran Sistem'],
        ['label' => 'Pengguna & Skop', 'url' => route('users.index')],
        ['label' => 'Tambah Pengguna']
    ]" />

    <div class="max-w-3xl mx-auto">
        <x-ui.card title="Daftar Pengguna Sistem Baharu" description="Sila masukkan maklumat akaun, peranan sistem dan skop kuasa pentadbiran">
            <form method="POST" action="{{ route('users.store') }}" class="space-y-6">
                @csrf

                <!-- Maklumat Akaun -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-3">
                        1. Maklumat Akaun
                    </h4>

                    <div
                        class="space-y-4"
                        x-data="{
                            nama: {{ json_encode(old('nama', '')) }},
                            email: {{ json_encode(old('email', '')) }},
                            isLinked: {{ old('anggota_id') ? 'true' : 'false' }},
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
                            :value="old('anggota_id')"
                            placeholder="Cari nama, no. pekerja, atau jawatan..."
                            hint="Pilih profil anggota daripada Direktori Anggota untuk membolehkan pewarisan skop bahagian/unit dan semakan kehadiran."
                        />

                        <div>
                            <x-form.input
                                name="nama"
                                label="Nama Penuh Pengguna"
                                placeholder="Contoh: Mohd Firdaus Bin Ismail"
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
                                placeholder="firdaus@ehadir.gov.my"
                                x-model="email"
                                required
                            />

                            <x-form.input
                                name="password"
                                type="password"
                                label="Kata Laluan"
                                placeholder="Minimum 8 aksara"
                                required
                            />
                        </div>

                        <div class="pt-2">
                            <x-form.toggle
                                name="aktif"
                                label="Akaun Aktif"
                                :checked="true"
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
                                    {{ is_array(old('role_ids')) && in_array($r->id, old('role_ids')) ? 'checked' : ($r->kod === 'ADMIN' ? 'checked' : '') }}
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
                    @error('role_ids')
                        <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Skop Kuasa Pentadbiran (User Scopes) -->
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
                                            {{ is_array(old('scopes_bahagian')) && in_array($b->id, old('scopes_bahagian')) ? 'checked' : '' }}
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
                                            {{ is_array(old('scopes_unit')) && in_array($u->id, old('scopes_unit')) ? 'checked' : '' }}
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
                    <x-ui.button href="{{ route('users.index') }}" variant="secondary">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Simpan Pengguna
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
