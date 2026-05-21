<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    protected $fillable = [
        'user_id',
        'nama_sepatu',
        'ukuran',
        'kondisi',
        'deskripsi',
        'foto_path',
        'metode_pengiriman',
        'nama_ekspedisi',
        'no_resi',
        'status',
        'catatan_admin',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'kondisi'     => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    // ── Relasi ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ── Accessor ──────────────────────────────────────────────────────

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_path
            ? asset('storage/' . $this->foto_path)
            : null;
    }

    // ── Scope ─────────────────────────────────────────────────────────

    public function scopePublicDisplay($query)
    {
        return $query->whereIn('status', ['diterima', 'disalurkan'])
                     ->latest('updated_at');
    }
}
