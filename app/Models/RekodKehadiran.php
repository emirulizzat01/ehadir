<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RekodKehadiran extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rekod_kehadiran';

    protected $fillable = [
        'anggota_id',
        'penempatan_id',
        'status_id',
        'tarikh',
        'sepanjang_hari',
        'masa_mula',
        'masa_tamat',
        'lokasi',
        'catatan',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tarikh' => 'date',
            'sepanjang_hari' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'anggota_id');
    }

    public function penempatan(): BelongsTo
    {
        return $this->belongsTo(AnggotaPenempatan::class, 'penempatan_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(StatusKehadiran::class, 'status_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
