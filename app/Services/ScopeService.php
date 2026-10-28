<?php

namespace App\Services;

use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ScopeService
{
    /**
     * Check if a user can access a given bahagian.
     */
    public function canAccessBahagian(User $user, int $bahagianId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return in_array($bahagianId, $user->getScopedBahagianIds());
    }

    /**
     * Check if a user can access a given unit.
     */
    public function canAccessUnit(User $user, int $unitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return in_array($unitId, $user->getScopedUnitIds());
    }

    /**
     * Check if a user can access an anggota at a specific date based on historical penempatan.
     */
    public function canAccessAnggotaAtDate(User $user, int $anggotaId, ?string $date = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $targetDate = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();

        $penempatan = AnggotaPenempatan::where('anggota_id', $anggotaId)
            ->where('tarikh_mula', '<=', $targetDate)
            ->where(function ($q) use ($targetDate) {
                $q->whereNull('tarikh_tamat')
                    ->orWhere('tarikh_tamat', '>=', $targetDate);
            })
            ->first();

        if (! $penempatan) {
            return false;
        }

        return $this->canAccessPenempatan($user, $penempatan);
    }

    /**
     * Check if a user can access a specific penempatan.
     */
    public function canAccessPenempatan(User $user, AnggotaPenempatan $penempatan): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $bahagianIds = $user->getScopedBahagianIds();
        $unitIds = $user->getScopedUnitIds();

        if ($penempatan->bahagian_id && in_array($penempatan->bahagian_id, $bahagianIds)) {
            return true;
        }

        if ($penempatan->unit_id && in_array($penempatan->unit_id, $unitIds)) {
            return true;
        }

        return false;
    }

    /**
     * Scope an Anggota query to only those currently (or at given date) assigned to the user's scope.
     */
    public function scopeAnggota(Builder $query, User $user, ?string $date = null): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $targetDate = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();
        $bahagianIds = $user->getScopedBahagianIds();
        $unitIds = $user->getScopedUnitIds();

        return $query->whereHas('penempatan', function ($q) use ($targetDate, $bahagianIds, $unitIds) {
            $q->where('tarikh_mula', '<=', $targetDate)
                ->where(function ($sq) use ($targetDate) {
                    $sq->whereNull('tarikh_tamat')
                        ->orWhere('tarikh_tamat', '>=', $targetDate);
                })
                ->where(function ($sq) use ($bahagianIds, $unitIds) {
                    $sq->whereIn('bahagian_id', $bahagianIds)
                        ->orWhereIn('unit_id', $unitIds);
                });
        });
    }

    /**
     * Scope RekodKehadiran query to only records within user's scope via historical penempatan.
     */
    public function scopeRekodKehadiran(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $bahagianIds = $user->getScopedBahagianIds();
        $unitIds = $user->getScopedUnitIds();

        return $query->whereHas('penempatan', function ($q) use ($bahagianIds, $unitIds) {
            $q->whereIn('bahagian_id', $bahagianIds)
                ->orWhereIn('unit_id', $unitIds);
        });
    }
}
