<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnggotaPenempatan extends Model
{
    use HasFactory;

    protected $table = 'anggota_penempatan';

    public $timestamps = false;

    protected $fillable = [
        'anggota_id',
        'bahagian_id',
        'unit_id',
        'tarikh_mula',
        'tarikh_tamat',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tarikh_mula' => 'date',
            'tarikh_tamat' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'anggota_id');
    }

    public function bahagian(): BelongsTo
    {
        return $this->belongsTo(Bahagian::class, 'bahagian_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function kehadiran(): HasMany
    {
        return $this->hasMany(RekodKehadiran::class, 'penempatan_id');
    }

    public function getLokasiPenempatanAttribute(): string
    {
        if ($this->bahagian) {
            return $this->bahagian->nama;
        }

        if ($this->unit) {
            return $this->unit->nama;
        }

        return 'Tidak Dinyatakan';
    }

    public function getNamaPenempatanAttribute(): string
    {
        return $this->lokasi_penempatan;
    }

    public function getJenisPenempatanAttribute(): string
    {
        return $this->bahagian_id ? 'Bahagian' : 'Unit';
    }

    public function isAktif(?string $date = null): bool
    {
        $target = $date ?: now()->toDateString();
        $mula = $this->tarikh_mula?->toDateString();
        $tamat = $this->tarikh_tamat?->toDateString();

        return $mula && $mula <= $target && (is_null($tamat) || $tamat >= $target);
    }
}
