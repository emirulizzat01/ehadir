<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatusKehadiran extends Model
{
    use HasFactory;

    protected $table = 'status_kehadiran';

    public $timestamps = false;

    protected $fillable = [
        'kod',
        'nama',
        'warna',
        'aktif',
        'susunan',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'susunan' => 'integer',
        ];
    }

    public function rekodKehadiran(): HasMany
    {
        return $this->hasMany(RekodKehadiran::class, 'status_id');
    }
}
