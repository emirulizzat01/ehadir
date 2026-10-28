<?php

namespace App\Services;

use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\RekodKehadiran;
use App\Models\StatusKehadiran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        protected ScopeService $scopeService
    ) {}

    /**
     * Resolve the valid placement covering the attendance date.
     * Throws ValidationException if no valid placement exists.
     */
    public function resolvePlacement(int $anggotaId, string $tarikh): AnggotaPenempatan
    {
        $date = Carbon::parse($tarikh)->toDateString();

        $penempatan = AnggotaPenempatan::where('anggota_id', $anggotaId)
            ->where('tarikh_mula', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('tarikh_tamat')
                    ->orWhere('tarikh_tamat', '>=', $date);
            })
            ->first();

        if (! $penempatan) {
            $anggota = Anggota::find($anggotaId);
            $nama = $anggota ? $anggota->nama_penuh : "ID {$anggotaId}";
            throw ValidationException::withMessages([
                'tarikh' => "Anggota ({$nama}) tiada penempatan yang sah pada tarikh {$date}.",
            ]);
        }

        return $penempatan;
    }

    /**
     * Record or update attendance for an employee on a given date.
     * Enforces placement coverage (Invariant 2), scope authorization (Invariant 3),
     * and soft-deleted duplicate collision handling (Invariant 5).
     */
    public function recordAttendance(array $data, ?User $actor = null): RekodKehadiran
    {
        return DB::transaction(function () use ($data, $actor) {
            $anggotaId = $data['anggota_id'];
            $tarikh = Carbon::parse($data['tarikh'])->toDateString();

            // 1. Resolve and validate placement coverage (Invariant 2)
            $penempatan = $this->resolvePlacement($anggotaId, $tarikh);

            // 2. Validate actor scope on this placement (Invariant 3) if actor is provided
            if ($actor && ! $this->scopeService->canAccessPenempatan($actor, $penempatan)) {
                throw ValidationException::withMessages([
                    'anggota_id' => 'Anda tidak mempunyai kebenaran untuk mengurus kehadiran anggota dalam bahagian/unit ini.',
                ]);
            }

            // 3. Validate time range if specified
            $sepanjangHari = $data['sepanjang_hari'] ?? true;
            $masaMula = (! $sepanjangHari && ! empty($data['masa_mula'])) ? $data['masa_mula'] : null;
            $masaTamat = (! $sepanjangHari && ! empty($data['masa_tamat'])) ? $data['masa_tamat'] : null;

            if ($masaMula && $masaTamat && $masaTamat <= $masaMula) {
                throw ValidationException::withMessages([
                    'masa_tamat' => 'Masa tamat mesti selepas masa mula.',
                ]);
            }

            // 4. Invariant 5: Check existing record including soft-deleted ones (due to uq_harian)
            $existing = RekodKehadiran::withTrashed()
                ->where('anggota_id', $anggotaId)
                ->whereDate('tarikh', $tarikh)
                ->first();

            $recordData = [
                'anggota_id' => $anggotaId,
                'penempatan_id' => $penempatan->id,
                'status_id' => $data['status_id'],
                'tarikh' => $tarikh,
                'sepanjang_hari' => $sepanjangHari,
                'masa_mula' => $masaMula,
                'masa_tamat' => $masaTamat,
                'lokasi' => $data['lokasi'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'updated_by' => $actor?->id,
            ];

            if ($existing) {
                $oldData = $existing->toArray();
                $isRestoring = $existing->trashed();

                if ($isRestoring) {
                    $existing->restore();
                }

                $existing->update($recordData);

                AuditLoggerService::log(
                    tindakan: $isRestoring ? 'RESTORE_UPDATE' : 'UPDATE',
                    namaJadual: 'rekod_kehadiran',
                    recordId: $existing->id,
                    dataLama: $oldData,
                    dataBaru: $existing->fresh()->toArray(),
                    userId: $actor?->id
                );

                return $existing;
            }

            // Fresh insert
            $recordData['created_by'] = $actor?->id;
            $rekod = RekodKehadiran::create($recordData);

            AuditLoggerService::log(
                tindakan: 'CREATE',
                namaJadual: 'rekod_kehadiran',
                recordId: $rekod->id,
                dataLama: null,
                dataBaru: $rekod->toArray(),
                userId: $actor?->id
            );

            return $rekod;
        });
    }

    /**
     * Automatically initialize default 'Pejabat' attendance records for all active staff
     * who have a valid placement covering the specified date and do not yet have a record.
     *
     * @return array{total_active: int, already_recorded: int, created: int, skipped_no_placement: int, dry_run: bool}
     */
    public function initializeDailyDefaults(string $tarikh, ?User $actor = null, bool $dryRun = false): array
    {
        $date = Carbon::parse($tarikh)->toDateString();

        $statusPejabat = StatusKehadiran::where('kod', 'PEJABAT')->first();
        if (! $statusPejabat) {
            throw new \RuntimeException("Status kehadiran bagi kod 'PEJABAT' tidak dijumpai dalam pangkalan data.");
        }

        $activeAnggota = Anggota::where('aktif', true)
            ->with(['penempatan' => function ($q) use ($date) {
                $q->where('tarikh_mula', '<=', $date)
                    ->where(function ($sq) use ($date) {
                        $sq->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $date);
                    })
                    ->orderByDesc('tarikh_mula');
            }])
            ->orderBy('id')
            ->get();

        $existingAnggotaIds = RekodKehadiran::withTrashed()
            ->whereDate('tarikh', $date)
            ->pluck('anggota_id')
            ->all();
        $existingMap = array_flip($existingAnggotaIds);

        $alreadyRecordedCount = 0;
        $skippedNoPlacementCount = 0;
        $recordsToInsert = [];
        $now = now()->toDateTimeString();

        foreach ($activeAnggota as $anggota) {
            if (isset($existingMap[$anggota->id])) {
                $alreadyRecordedCount++;
                continue;
            }

            $activePenempatan = $anggota->penempatan->first()
                ?? $anggota->getPenempatanAtDate($date)
                ?? $anggota->penempatanSemasa()
                ?? $anggota->penempatan()->orderByDesc('tarikh_mula')->first();

            if (! $activePenempatan) {
                $skippedNoPlacementCount++;
                continue;
            }

            $recordsToInsert[] = [
                'anggota_id' => $anggota->id,
                'penempatan_id' => $activePenempatan->id,
                'status_id' => $statusPejabat->id,
                'tarikh' => $date,
                'sepanjang_hari' => true,
                'masa_mula' => null,
                'masa_tamat' => null,
                'lokasi' => null,
                'catatan' => null,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! $dryRun && ! empty($recordsToInsert)) {
            DB::transaction(function () use ($recordsToInsert, $date, $actor) {
                foreach (array_chunk($recordsToInsert, 100) as $chunk) {
                    RekodKehadiran::insert($chunk);
                }

                AuditLoggerService::log(
                    tindakan: 'BATCH_DEFAULT_PEJABAT',
                    namaJadual: 'rekod_kehadiran',
                    recordId: null,
                    dataLama: null,
                    dataBaru: [
                        'tarikh' => $date,
                        'count' => count($recordsToInsert),
                        'status' => 'PEJABAT',
                    ],
                    userId: $actor?->id
                );
            });
        }

        return [
            'total_active' => $activeAnggota->count(),
            'already_recorded' => $alreadyRecordedCount,
            'created' => count($recordsToInsert),
            'skipped_no_placement' => $skippedNoPlacementCount,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * Batch check-in or mark attendance for multiple staff members.
     */
    public function batchRecordAttendance(array $anggotaIds, int $statusId, string $tarikh, User $actor): int
    {
        $count = 0;
        foreach ($anggotaIds as $id) {
            $this->recordAttendance([
                'anggota_id' => $id,
                'status_id' => $statusId,
                'tarikh' => $tarikh,
                'sepanjang_hari' => true,
            ], $actor);
            $count++;
        }

        return $count;
    }

    /**
     * Soft delete an attendance record with audit trail.
     */
    public function deleteAttendance(RekodKehadiran $rekod, User $actor): void
    {
        DB::transaction(function () use ($rekod, $actor) {
            if (! $this->scopeService->canAccessPenempatan($actor, $rekod->penempatan)) {
                throw ValidationException::withMessages([
                    'record' => 'Anda tidak mempunyai kebenaran untuk memadam rekod ini.',
                ]);
            }

            $oldData = $rekod->toArray();
            $rekod->delete();

            AuditLoggerService::log(
                tindakan: 'DELETE',
                namaJadual: 'rekod_kehadiran',
                recordId: $rekod->id,
                dataLama: $oldData,
                dataBaru: null,
                userId: $actor->id
            );
        });
    }
}
