<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'unit';

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
        return $this->hasMany(AnggotaPenempatan::class, 'unit_id');
    }

    public function userScopes(): HasMany
    {
        return $this->hasMany(UserScope::class, 'unit_id');
    }
}
