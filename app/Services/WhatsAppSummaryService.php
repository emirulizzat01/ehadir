<?php

namespace App\Services;

use App\Models\Anggota;
use App\Models\Bahagian;
use App\Models\RekodKehadiran;
use App\Models\StatusKehadiran;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WhatsAppSummaryService
{
    public function __construct(
        protected ScopeService $scopeService
    ) {}

    /**
     * Generate formatted WhatsApp text by querying database for specified date and scope.
     */
    public function generateForDate(
        string $tarikh,
        ?int $bahagianId = null,
        ?int $unitId = null,
        string $format = 'exceptions',
        ?User $user = null
    ): array {
        $date = Carbon::parse($tarikh)->toDateString();

        // 1. Determine Scope Label & Query Anggota
        $anggotaQuery = Anggota::where('aktif', true)
            ->with(['penempatan' => function ($q) use ($date) {
                $q->with(['bahagian', 'unit'])
                    ->where('tarikh_mula', '<=', $date)
                    ->where(function ($sq) use ($date) {
                        $sq->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $date);
                    });
            }]);

        if ($user) {
            $this->scopeService->scopeAnggota($anggotaQuery, $user, $date);
        }

        $scopeLabel = 'Lembaga Kemajuan Johor Tenggara (KEJORA)';

        if ($bahagianId) {
            $bahagian = Bahagian::find($bahagianId);
            if ($bahagian) {
                $scopeLabel = 'Bahagian ' . $bahagian->nama;
                $anggotaQuery->whereHas('penempatan', function ($q) use ($bahagianId, $date) {
                    $q->where('bahagian_id', $bahagianId)
                        ->where('tarikh_mula', '<=', $date)
                        ->where(function ($sq) use ($date) {
                            $sq->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $date);
                        });
                });
            }
        } elseif ($unitId) {
            $unit = Unit::find($unitId);
            if ($unit) {
                $scopeLabel = 'Unit ' . $unit->nama;
                $anggotaQuery->whereHas('penempatan', function ($q) use ($unitId, $date) {
                    $q->where('unit_id', $unitId)
                        ->where('tarikh_mula', '<=', $date)
                        ->where(function ($sq) use ($date) {
                            $sq->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $date);
                        });
                });
            }
        } elseif ($user && ! $user->isSuperAdmin()) {
            $scopedBahagianIds = $user->getScopedBahagianIds();
            if (! empty($scopedBahagianIds)) {
                $scopedBahagian = Bahagian::find($scopedBahagianIds[0]);
                if ($scopedBahagian) {
                    $scopeLabel = 'Bahagian ' . $scopedBahagian->nama;
                }
            }
        }

        $anggotaList = $anggotaQuery->orderBy('susunan')->orderBy('nama_penuh')->get();
        $anggotaIds = $anggotaList->pluck('id')->all();

        // 2. Fetch Attendance Records for this Date
        $records = RekodKehadiran::with(['status', 'penempatan.bahagian', 'penempatan.unit'])
            ->whereDate('tarikh', $date)
            ->whereIn('anggota_id', $anggotaIds)
            ->get()
            ->keyBy('anggota_id');

        return $this->formatFromData($date, $scopeLabel, $anggotaList, $records, $format);
    }

    /**
     * Build the formatted WhatsApp message from preloaded data collections.
     *
     * @param  Collection<int, Anggota>  $anggotaList
     * @param  Collection<int, RekodKehadiran>  $records
     */
    public function formatFromData(
        string $tarikh,
        string $scopeLabel,
        Collection $anggotaList,
        Collection $records,
        string $format = 'exceptions'
    ): array {
        $carbonDate = Carbon::parse($tarikh);
        $totalAnggota = $anggotaList->count();

        // Categorize Anggota
        $pejabatList = [];
        $dutyList = [];
        $leaveList = [];
        $fourHourList = [];
        $unrecordedList = [];

        foreach ($anggotaList as $anggota) {
            $record = $records->get($anggota->id);

            if (! $record) {
                $unrecordedList[] = [
                    'anggota' => $anggota,
                    'details' => 'Belum Direkod',
                ];
                continue;
            }

            $kod = strtoupper($record->status?->kod ?? '');
            $nama = $anggota->nama_penuh_gelaran ?: $anggota->nama_penuh;

            switch ($kod) {
                case 'PEJABAT':
                    $pejabatList[] = [
                        'anggota' => $anggota,
                        'details' => 'Pejabat',
                    ];
                    break;

                case 'KERJA_LUAR':
                    $detail = $record->lokasi ?: ($record->catatan ?: 'Tugas Rasmi');
                    $dutyList[] = [
                        'anggota' => $anggota,
                        'details' => $detail,
                    ];
                    break;

                case 'CUTI_REHAT':
                case 'MC':
                    $leaveName = $record->status?->nama ?? 'Cuti';
                    if ($record->catatan) {
                        $leaveName .= ' (' . $record->catatan . ')';
                    }
                    $leaveList[] = [
                        'anggota' => $anggota,
                        'details' => $leaveName,
                    ];
                    break;

                case 'EMPAT_JAM':
                    $timeDetail = 'Kebenaran 4 Jam';
                    if ($record->masa_mula && $record->masa_tamat) {
                        $timeDetail .= " ({$record->masa_mula} - {$record->masa_tamat})";
                    }
                    if ($record->catatan) {
                        $timeDetail .= ' - ' . $record->catatan;
                    }
                    $fourHourList[] = [
                        'anggota' => $anggota,
                        'details' => $timeDetail,
                    ];
                    break;

                default:
                    $leaveList[] = [
                        'anggota' => $anggota,
                        'details' => $record->status?->nama ?? 'Pengecualian',
                    ];
                    break;
            }
        }

        $pejabatCount = count($pejabatList);
        $dutyCount = count($dutyList);
        $leaveCount = count($leaveList);
        $fourHourCount = count($fourHourList);
        $unrecordedCount = count($unrecordedList);

        $presenceRate = $totalAnggota > 0 ? round(($pejabatCount / $totalAnggota) * 100, 1) : 0.0;
        $dutyRate = $totalAnggota > 0 ? round(($dutyCount / $totalAnggota) * 100, 1) : 0.0;
        $leaveRate = $totalAnggota > 0 ? round(($leaveCount / $totalAnggota) * 100, 1) : 0.0;
        $unrecordedRate = $totalAnggota > 0 ? round(($unrecordedCount / $totalAnggota) * 100, 1) : 0.0;

        // Build WhatsApp Message Lines
        $lines = [];
        $lines[] = '📊 *STATUS KEHADIRAN HARIAN KEJORA*';
        $lines[] = '🏢 *' . $scopeLabel . '*';
        $lines[] = '📅 *' . $carbonDate->translatedFormat('l, d F Y') . '*';
        $lines[] = '⏰ Masa Laporan: *' . now()->format('h:i A') . '*';
        $lines[] = '━━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '📈 *RINGKASAN KEHADIRAN*';
        $lines[] = "• Jumlah Anggota: *{$totalAnggota} orang*";
        $lines[] = "• Hadir di Pejabat: *{$pejabatCount} orang* ({$presenceRate}%)";
        $lines[] = "• Bertugas Luar: *{$dutyCount} orang* ({$dutyRate}%)";
        $lines[] = "• Cuti / MC: *{$leaveCount} orang* ({$leaveRate}%)";
        if ($fourHourCount > 0) {
            $lines[] = "• Kebenaran 4 Jam: *{$fourHourCount} orang*";
        }
        $lines[] = "• Belum Direkod: *{$unrecordedCount} orang* ({$unrecordedRate}%)";
        $lines[] = '━━━━━━━━━━━━━━━━━━━━━';

        if ($format !== 'stats') {
            // 1. Bertugas Luar
            $lines[] = '';
            $lines[] = "🚗 *BERTUGAS RASMI / KERJA LUAR ({$dutyCount})*";
            if ($dutyCount > 0) {
                foreach ($dutyList as $i => $item) {
                    $num = $i + 1;
                    $nama = $item['anggota']->nama_penuh_gelaran ?: $item['anggota']->nama_penuh;
                    $lines[] = "{$num}. {$nama} - _{$item['details']}_";
                }
            } else {
                $lines[] = '- Tiada';
            }

            // 2. Cuti / MC
            $lines[] = '';
            $lines[] = "🏖️ *CUTI REHAT / SAKIT (MC) ({$leaveCount})*";
            if ($leaveCount > 0) {
                foreach ($leaveList as $i => $item) {
                    $num = $i + 1;
                    $nama = $item['anggota']->nama_penuh_gelaran ?: $item['anggota']->nama_penuh;
                    $lines[] = "{$num}. {$nama} - _{$item['details']}_";
                }
            } else {
                $lines[] = '- Tiada';
            }

            // 3. Kebenaran 4 Jam
            if ($fourHourCount > 0 || $format === 'full') {
                $lines[] = '';
                $lines[] = "⏱️ *KEBENARAN 4 JAM ({$fourHourCount})*";
                if ($fourHourCount > 0) {
                    foreach ($fourHourList as $i => $item) {
                        $num = $i + 1;
                        $nama = $item['anggota']->nama_penuh_gelaran ?: $item['anggota']->nama_penuh;
                        $lines[] = "{$num}. {$nama} - _{$item['details']}_";
                    }
                } else {
                    $lines[] = '- Tiada';
                }
            }

            // 4. Belum Direkod (if any)
            if ($unrecordedCount > 0) {
                $lines[] = '';
                $lines[] = "❓ *BELUM DIREKOD ({$unrecordedCount})*";
                foreach ($unrecordedList as $i => $item) {
                    $num = $i + 1;
                    $nama = $item['anggota']->nama_penuh_gelaran ?: $item['anggota']->nama_penuh;
                    $lines[] = "{$num}. {$nama}";
                }
            }

            // 5. Hadir di Pejabat
            $lines[] = '';
            $lines[] = "🏢 *HADIR DI PEJABAT ({$pejabatCount})*";
            if ($format === 'full') {
                if ($pejabatCount > 0) {
                    foreach ($pejabatList as $i => $item) {
                        $num = $i + 1;
                        $nama = $item['anggota']->nama_penuh_gelaran ?: $item['anggota']->nama_penuh;
                        $lines[] = "{$num}. {$nama}";
                    }
                } else {
                    $lines[] = '- Tiada anggota di pejabat.';
                }
            } else {
                // Exceptions format
                $lines[] = "- {$pejabatCount} orang anggota bertugas di pejabat.";
            }

            $lines[] = '';
            $lines[] = '━━━━━━━━━━━━━━━━━━━━━';
        }

        $lines[] = '_Dijana secara automatik melalui Sistem eHadir KEJORA_';

        $text = implode("\n", $lines);

        return [
            'success' => true,
            'tarikh' => $tarikh,
            'scope_label' => $scopeLabel,
            'format' => $format,
            'text' => $text,
            'stats' => [
                'total' => $totalAnggota,
                'pejabat' => $pejabatCount,
                'duty' => $dutyCount,
                'leave' => $leaveCount,
                'four_hour' => $fourHourCount,
                'unrecorded' => $unrecordedCount,
                'presence_rate' => $presenceRate,
            ],
        ];
    }
}
