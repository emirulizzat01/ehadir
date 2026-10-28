<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bahagian extends Model
{
    use HasFactory;

    protected $table = 'bahagian';

    protected $fillable = [
        'kod',
        'nama',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function penempatan(): HasMany
    {
        return $this->hasMany(AnggotaPenempatan::class, 'bahagian_id');
    }

    public function userScopes(): HasMany
    {
        return $this->hasMany(UserScope::class, 'bahagian_id');
    }
}
