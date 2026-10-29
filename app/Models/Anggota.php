<?php

namespace App\Models;

use App\Services\Personnel\GradeHierarchyService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Anggota extends Model
{
    use HasFactory;

    protected $table = 'anggota';

    protected $fillable = [
        'onestack_id',
        'no_pekerja',
        'nama_penuh',
        'gelaran',
        'jawatan',
        'gred',
        'tahap_hierarki',
        'emel',
        'no_telefon',
        'aktif',
        'tarikh_bersara',
        'susunan',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'onestack_id' => 'integer',
            'tahap_hierarki' => 'integer',
            'aktif' => 'boolean',
            'tarikh_bersara' => 'date',
            'susunan' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Anggota $anggota) {
            if ($anggota->isDirty(['gred', 'jawatan']) || ! isset($anggota->tahap_hierarki)) {
                $service = app(GradeHierarchyService::class);
                $anggota->tahap_hierarki = $service->calculateScore($anggota->gred, $anggota->jawatan);
            }
        });
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'anggota_id');
    }

    public function penempatan(): HasMany
    {
        return $this->hasMany(AnggotaPenempatan::class, 'anggota_id')->orderBy('tarikh_mula', 'desc');
    }

    public function kehadiran(): HasMany
    {
        return $this->hasMany(RekodKehadiran::class, 'anggota_id');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(CalendarEvent::class, 'calendar_event_attendees', 'anggota_id', 'event_id');
    }

    /**
     * Get the active or specific-date penempatan for this anggota.
     */
    public function getPenempatanAtDate($tarikh = null): ?AnggotaPenempatan
    {
        $date = $tarikh ? Carbon::parse($tarikh)->toDateString() : now()->toDateString();

        return $this->penempatan()
            ->with(['bahagian', 'unit'])
            ->where('tarikh_mula', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('tarikh_tamat')
                    ->orWhere('tarikh_tamat', '>=', $date);
            })
            ->first();
    }

    /**
     * Get current active penempatan for this anggota.
     */
    public function penempatanSemasa(): ?AnggotaPenempatan
    {
        return $this->getPenempatanAtDate(now());
    }

    /**
     * Get active placement, optimizing from eager-loaded collection when present.
     */
    public function getPenempatanAktifAttribute(): ?AnggotaPenempatan
    {
        if ($this->relationLoaded('penempatan')) {
            return $this->penempatan->first(fn ($p) => $p->isAktif());
        }

        return $this->penempatanSemasa();
    }

    /**
     * Accessor for full display name including gelaran.
     */
    public function getNamaPenuhGelaranAttribute(): string
    {
        return $this->gelaran ? "{$this->gelaran} {$this->nama_penuh}" : $this->nama_penuh;
    }

    /**
     * Accessor for hierarchy category title.
     */
    public function getKategoriHierarkiAttribute(): string
    {
        return app(GradeHierarchyService::class)->getCategoryLabel($this->tahap_hierarki ?? 100);
    }

    /**
     * Scope to order anggota by public service hierarchy (highest to lowest grade).
     */
    public function scopeOrderByHierarchy($query, string $direction = 'desc')
    {
        return $query->orderBy('tahap_hierarki', $direction)
            ->orderBy('susunan', 'asc')
            ->orderBy('nama_penuh', 'asc');
    }
}
