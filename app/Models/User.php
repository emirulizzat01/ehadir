<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'anggota_id',
        'nama',
        'email',
        'password_hash',
        'aktif',
        'last_login',
        'calendar_feed_token',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'last_login' => 'datetime',
        ];
    }

    /**
     * Get the password for authentication.
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    public function scopes(): HasMany
    {
        return $this->hasMany(UserScope::class, 'user_id');
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'anggota_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles->contains('kod', 'SUPERADMIN');
    }

    public function hasRole(string $roleCode): bool
    {
        return $this->roles->contains('kod', $roleCode);
    }

    public function hasPermission(string $permissionCode): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($this->roles as $role) {
            if ($role->permissions->contains('kod', $permissionCode)) {
                return true;
            }
        }

        return false;
    }

    public function getScopedBahagianIds(): array
    {
        $explicit = $this->scopes->whereNotNull('bahagian_id')->pluck('bahagian_id')->all();

        // Auto-inherit from linked Anggota's active placement if no explicit scopes configured
        if (empty($explicit) && $this->anggota) {
            $placement = $this->anggota->penempatanSemasa();
            if ($placement && $placement->bahagian_id) {
                return [$placement->bahagian_id];
            }
        }

        return $explicit;
    }

    public function getScopedUnitIds(): array
    {
        $explicit = $this->scopes->whereNotNull('unit_id')->pluck('unit_id')->all();

        // Auto-inherit from linked Anggota's active placement if no explicit scopes configured
        if (empty($explicit) && $this->anggota) {
            $placement = $this->anggota->penempatanSemasa();
            if ($placement && $placement->unit_id) {
                return [$placement->unit_id];
            }
        }

        return $explicit;
    }

    public function getOrCreateCalendarFeedToken(): string
    {
        if (! $this->calendar_feed_token) {
            $this->calendar_feed_token = bin2hex(random_bytes(32));
            $this->save();
        }

        return $this->calendar_feed_token;
    }

    public function regenerateCalendarFeedToken(): string
    {
        $this->calendar_feed_token = bin2hex(random_bytes(32));
        $this->save();

        return $this->calendar_feed_token;
    }

    public function getCalendarFeedUrl(): string
    {
        return route('calendar.feed', ['token' => $this->getOrCreateCalendarFeedToken()]);
    }
}
