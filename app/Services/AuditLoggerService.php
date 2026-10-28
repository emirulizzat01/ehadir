<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLoggerService
{
    /**
     * Record an audit log entry.
     */
    public static function log(
        string $tindakan,
        string $namaJadual,
        ?int $recordId = null,
        ?array $dataLama = null,
        ?array $dataBaru = null,
        ?int $userId = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'tindakan' => $tindakan,
            'nama_jadual' => $namaJadual,
            'record_id' => $recordId,
            'data_lama' => $dataLama,
            'data_baru' => $dataBaru,
            'created_at' => now(),
        ]);
    }
}
