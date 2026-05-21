<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nama',
        'email',
        'password',
        'role',
        'avatar_path',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'   => 'hashed',
            'is_active'  => 'boolean',
        ];
    }

    // ── Relasi ────────────────────────────────────────────────────────

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function dailyLogins(): HasMany
    {
        return $this->hasMany(DailyLogin::class);
    }

    public function userRewards(): HasMany
    {
        return $this->hasMany(UserReward::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path
            ? asset('storage/' . $this->avatar_path)
            : null;
    }
}
