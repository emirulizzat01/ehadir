<x-layouts.app title="Butiran Rekod Kehadiran">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Kehadiran', 'url' => route('attendance.index')],
        ['label' => 'Butiran Rekod']
    ]" />

    <div class="max-w-2xl mx-auto space-y-6">
        <x-ui.card :title="'Rekod Kehadiran: ' . $attendance->tarikh->format('d/m/Y')" :description="$attendance->anggota->nama_penuh_gelaran">
            <x-slot:actions>
                @if (auth()->user()?->hasPermission('attendance.manage'))
                    <x-ui.button href="{{ route('attendance.edit', $attendance) }}" variant="secondary" size="sm">
                        <x-icon name="pencil" class="w-4 h-4 text-emerald-600" />
                        <span>Kemaskini</span>
                    </x-ui.button>
                @endif
            </x-slot:actions>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase">Status Kehadiran</p>
                        <div class="mt-1">
                            <x-ui.badge :color="$attendance->status->warna">
                                {{ $attendance->status->nama }}
                            </x-ui.badge>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase">Tarikh</p>
                        <p class="text-sm font-bold text-slate-900 dark:text-white mt-1">
                            {{ $attendance->tarikh->translatedFormat('l, d F Y') }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase">Tempoh Waktu</p>
                        <p class="text-sm text-slate-800 dark:text-slate-200 mt-1">
                            {{ $attendance->sepanjang_hari ? 'Sepanjang Hari' : "{$attendance->masa_mula} hingga {$attendance->masa_tamat}" }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase">Penempatan Berkuat Kuasa</p>
                        <p class="text-sm text-slate-800 dark:text-slate-200 mt-1">
                            {{ $attendance->penempatan->lokasi_penempatan ?? '-' }}
                        </p>
                    </div>
                </div>

                @if ($attendance->lokasi)
                    <div class="pb-4 border-b border-slate-200 dark:border-slate-800">
                        <p class="text-xs font-semibold text-slate-500 uppercase">Lokasi / Tempat Bertugas</p>
                        <p class="text-sm text-slate-800 dark:text-slate-200 mt-1">{{ $attendance->lokasi }}</p>
                    </div>
                @endif

                @if ($attendance->catatan)
                    <div class="pb-4 border-b border-slate-200 dark:border-slate-800">
                        <p class="text-xs font-semibold text-slate-500 uppercase">Catatan / Rujukan</p>
                        <p class="text-sm text-slate-800 dark:text-slate-200 mt-1 whitespace-pre-line">{{ $attendance->catatan }}</p>
                    </div>
                @endif

                <!-- Audit metadata -->
                <div class="pt-2 text-xs text-slate-400 dark:text-slate-500 space-y-1">
                    <p>Direkodkan oleh: <span class="font-medium text-slate-600 dark:text-slate-400">{{ $attendance->creator?->nama ?? 'Sistem' }}</span> pada {{ $attendance->created_at->format('d/m/Y H:i') }}</p>
                    <p>Kemaskini terakhir oleh: <span class="font-medium text-slate-600 dark:text-slate-400">{{ $attendance->updater?->nama ?? 'Sistem' }}</span> pada {{ $attendance->updated_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
