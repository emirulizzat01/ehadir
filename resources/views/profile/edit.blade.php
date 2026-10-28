<x-layouts.app title="Profil Saya">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Profil Saya']
    ]" />

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Section 1: Profil Peribadi -->
        <x-ui.card title="Maklumat Profil Peribadi" description="Kemaskini nama paparan dan alamat emel rasmi anda">
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-form.input
                        name="nama"
                        label="Nama Penuh"
                        :value="$user->nama"
                        required
                    />

                    <x-form.input
                        name="email"
                        type="email"
                        label="Alamat Emel"
                        :value="$user->email"
                        required
                    />
                </div>

                <div class="pt-3 flex justify-end">
                    <x-ui.button type="submit" variant="primary">
                        Simpan Maklumat Profil
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <!-- Section 2: Tukar Kata Laluan -->
        <x-ui.card title="Keselamatan & Kata Laluan" description="Pastikan akaun anda menggunakan kata laluan yang kukuh">
            <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-form.input
                        name="current_password"
                        type="password"
                        label="Kata Laluan Semasa"
                        placeholder="••••••••"
                        required
                    />

                    <x-form.input
                        name="password"
                        type="password"
                        label="Kata Laluan Baharu"
                        placeholder="Minimum 8 aksara"
                        required
                    />

                    <x-form.input
                        name="password_confirmation"
                        type="password"
                        label="Sahkan Kata Laluan"
                        placeholder="Ulang kata laluan baharu"
                        required
                    />
                </div>

                <div class="pt-3 flex justify-end">
                    <x-ui.button type="submit" variant="primary">
                        Tukar Kata Laluan
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <!-- Section 3: Maklumat Peranan & Skop Pentadbiran -->
        <x-ui.card title="Peranan & Had Skop Akses" description="Status kebenaran akaun anda dalam sistem eHadir">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                        Peranan Sistem (Roles)
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($user->roles as $role)
                            <x-ui.badge :variant="$role->kod === 'SUPERADMIN' ? 'purple' : 'info'">
                                {{ $role->nama }}
                            </x-ui.badge>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                        Skop Pentadbiran Bertugaskan
                    </h4>
                    @if ($user->isSuperAdmin())
                        <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            Akses Penuh Superadmin ke seluruh organisasi.
                        </p>
                    @elseif ($user->scopes->isEmpty())
                        <p class="text-xs text-slate-400 italic">
                            Tiada skop bahagian atau unit diberikan buat masa ini.
                        </p>
                    @else
                        <div class="space-y-1">
                            @foreach ($user->scopes as $scope)
                                <div class="text-xs font-medium text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                    <x-icon name="organization" class="w-3.5 h-3.5 text-emerald-600" />
                                    <span>{{ $scope->scope_name }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
