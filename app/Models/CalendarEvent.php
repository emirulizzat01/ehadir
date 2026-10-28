<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'calendar_events';

    protected $fillable = [
        'tajuk',
        'jenis',
        'mula',
        'tamat',
        'sepanjang_hari',
        'lokasi',
        'catatan',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'mula' => 'datetime',
            'tamat' => 'datetime',
            'sepanjang_hari' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(Anggota::class, 'calendar_event_attendees', 'event_id', 'anggota_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getJenisLabelAttribute(): string
    {
        return match ($this->jenis) {
            'MESYUARAT' => 'Mesyuarat',
            'BENGKEL' => 'Bengkel',
            'KURSUS' => 'Kursus',
            'LAWATAN_TAPAK' => 'Lawatan Tapak',
            default => 'Lain-lain',
        };
    }

    public function getGoogleCalendarUrlAttribute(): string
    {
        $start = $this->sepanjang_hari
            ? $this->mula->format('Ymd')
            : $this->mula->utc()->format('Ymd\THis\Z');

        $end = $this->sepanjang_hari
            ? $this->tamat->copy()->addDay()->format('Ymd')
            : $this->tamat->utc()->format('Ymd\THis\Z');

        $params = [
            'action' => 'TEMPLATE',
            'text' => $this->tajuk,
            'dates' => $start.'/'.$end,
            'details' => ($this->catatan ? $this->catatan."\n\n" : '').'Pautan eHadir: '.route('calendar.show', $this),
            'location' => $this->lokasi ?? '',
        ];

        return 'https://calendar.google.com/calendar/render?'.http_build_query($params);
    }
}
