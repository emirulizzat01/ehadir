<?php

namespace App\Services;

use App\Models\AnggotaPenempatan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenempatanService
{
    /**
     * Check if the given period overlaps with any existing placement for the employee.
     * Throws ValidationException if an overlap is detected.
     */
    public function validateNoOverlap(
        int $anggotaId,
        string $tarikhMula,
        ?string $tarikhTamat = null,
        ?int $ignoreId = null
    ): void {
        $mula = Carbon::parse($tarikhMula)->toDateString();
        $tamat = $tarikhTamat ? Carbon::parse($tarikhTamat)->toDateString() : null;

        if ($tamat !== null && $tamat < $mula) {
            throw ValidationException::withMessages([
                'tarikh_tamat' => 'Tarikh tamat tidak boleh lebih awal daripada tarikh mula.',
            ]);
        }

        $query = AnggotaPenempatan::where('anggota_id', $anggotaId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId));

        if ($tamat === null) {
            // New placement is open-ended.
            // Any existing placement where (tarikh_tamat IS NULL OR tarikh_tamat >= new_mula) overlaps.
            $query->where(function ($q) use ($mula) {
                $q->whereNull('tarikh_tamat')
                    ->orWhere('tarikh_tamat', '>=', $mula);
            });
        } else {
            // New placement has both start and end date.
            // Existing placement overlaps if:
            // existing.tarikh_mula <= new_tamat AND (existing.tarikh_tamat IS NULL OR existing.tarikh_tamat >= new_mula)
            $query->where('tarikh_mula', '<=', $tamat)
                ->where(function ($q) use ($mula) {
                    $q->whereNull('tarikh_tamat')
                        ->orWhere('tarikh_tamat', '>=', $mula);
                });
        }

        $conflicting = $query->first();

        if ($conflicting) {
            $conflictRange = $conflicting->tarikh_mula->format('d/m/Y').' - '.
                ($conflicting->tarikh_tamat ? $conflicting->tarikh_tamat->format('d/m/Y') : 'Kini');

            throw ValidationException::withMessages([
                'tarikh_mula' => "Tempoh penempatan bertindih dengan rekod sedia ada ({$conflictRange}).",
            ]);
        }
    }

    /**
     * Create a new placement record within a transaction and audit log.
     */
    public function createPenempatan(array $data, int $actorId): AnggotaPenempatan
    {
        return DB::transaction(function () use ($data, $actorId) {
            $this->validateNoOverlap(
                $data['anggota_id'],
                $data['tarikh_mula'],
                $data['tarikh_tamat'] ?? null
            );

            $penempatan = AnggotaPenempatan::create([
                'anggota_id' => $data['anggota_id'],
                'bahagian_id' => $data['bahagian_id'] ?? null,
                'unit_id' => $data['unit_id'] ?? null,
                'tarikh_mula' => $data['tarikh_mula'],
                'tarikh_tamat' => $data['tarikh_tamat'] ?? null,
                'created_at' => now(),
            ]);

            AuditLoggerService::log(
                tindakan: 'CREATE',
                namaJadual: 'anggota_penempatan',
                recordId: $penempatan->id,
                dataLama: null,
                dataBaru: $penempatan->toArray(),
                userId: $actorId
            );

            return $penempatan;
        });
    }

    /**
     * Transfer an anggota to a new placement, automatically closing the previous open placement.
     */
    public function transferAnggota(int $anggotaId, array $newPlacement, int $actorId): AnggotaPenempatan
    {
        return DB::transaction(function () use ($anggotaId, $newPlacement, $actorId) {
            $newMula = Carbon::parse($newPlacement['tarikh_mula'])->toDateString();

            // Find any currently open placement
            $currentActive = AnggotaPenempatan::where('anggota_id', $anggotaId)
                ->whereNull('tarikh_tamat')
                ->where('tarikh_mula', '<', $newMula)
                ->first();

            if ($currentActive) {
                $oldData = $currentActive->toArray();
                // Close the previous placement one day before the new placement starts
                $currentActive->update([
                    'tarikh_tamat' => Carbon::parse($newMula)->subDay()->toDateString(),
                ]);

                AuditLoggerService::log(
                    tindakan: 'UPDATE',
                    namaJadual: 'anggota_penempatan',
                    recordId: $currentActive->id,
                    dataLama: $oldData,
                    dataBaru: $currentActive->toArray(),
                    userId: $actorId
                );
            }

            // Now create the new placement
            $newPlacement['anggota_id'] = $anggotaId;

            return $this->createPenempatan($newPlacement, $actorId);
        });
    }

    /**
     * Update an existing placement.
     */
    public function updatePenempatan(AnggotaPenempatan $penempatan, array $data, int $actorId): AnggotaPenempatan
    {
        return DB::transaction(function () use ($penempatan, $data, $actorId) {
            $this->validateNoOverlap(
                $penempatan->anggota_id,
                $data['tarikh_mula'],
                $data['tarikh_tamat'] ?? null,
                $penempatan->id
            );

            $oldData = $penempatan->toArray();

            $penempatan->update([
                'bahagian_id' => $data['bahagian_id'] ?? null,
                'unit_id' => $data['unit_id'] ?? null,
                'tarikh_mula' => $data['tarikh_mula'],
                'tarikh_tamat' => $data['tarikh_tamat'] ?? null,
            ]);

            AuditLoggerService::log(
                tindakan: 'UPDATE',
                namaJadual: 'anggota_penempatan',
                recordId: $penempatan->id,
                dataLama: $oldData,
                dataBaru: $penempatan->toArray(),
                userId: $actorId
            );

            return $penempatan;
        });
    }
}
