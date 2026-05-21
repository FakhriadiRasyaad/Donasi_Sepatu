<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLogin extends Model
{
    public $timestamps = false; // Tabel pakai created_at manual

    protected $fillable = [
        'user_id',
        'tanggal_checkin',
        'foto_sepatu_path',
        'minggu_ke',
        'hari_ke',
        'reward_claimed',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_checkin' => 'date',
            'reward_claimed'  => 'boolean',
            'hari_ke'         => 'integer',
            'minggu_ke'       => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_sepatu_path
            ? asset('storage/' . $this->foto_sepatu_path)
            : null;
    }
}
