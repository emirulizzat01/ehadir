<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserScope extends Model
{
    use HasFactory;

    protected $table = 'user_scopes';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'bahagian_id',
        'unit_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bahagian(): BelongsTo
    {
        return $this->belongsTo(Bahagian::class, 'bahagian_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function getScopeNameAttribute(): string
    {
        if ($this->bahagian) {
            return 'Bahagian: '.$this->bahagian->nama;
        }

        if ($this->unit) {
            return 'Unit: '.$this->unit->nama;
        }

        return 'Tiada Skop';
    }
}
