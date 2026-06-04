<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    use HasFactory;

    protected $table = 'donations';

    protected $fillable = [
        'user_id',
        'nama_sepatu',
        'ukuran',
        'kondisi',
        'harga',
        'deskripsi',
        'foto_path',
        'foto_bukti_path',
        'metode_pengiriman',
        'nama_ekspedisi',
        'no_resi',
        'status',
        'catatan_admin',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'kondisi'     => 'integer',
        'harga'       => 'integer', // Tidak perlu decimal:0, bisa langsung integer
        'verified_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    // ── RELATIONSHIPS ──────────────────────────────────────────────────

    /**
     * Relasi ke User (pendonasi)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke User yang memverifikasi
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ── ACCESSORS ──────────────────────────────────────────────────────

    /**
     * Accessor untuk foto_url
     * Mengubah foto_path menjadi full URL
     */
    public function getFotoUrlAttribute(): ?string
    {
        if (!$this->foto_path) {
            return null;
        }

        // Jika sudah full URL, return as-is
        if (str_starts_with($this->foto_path, 'http')) {
            return $this->foto_path;
        }

        // Jika relatif, buat full URL
        return asset('storage/' . $this->foto_path);
    }

    /**
     * Accessor untuk foto_bukti_url
     * Foto bukti penerimaan sepatu dari admin
     */
    public function getFotoBuktiUrlAttribute(): ?string
    {
        if (!$this->foto_bukti_path) {
            return null;
        }

        if (str_starts_with($this->foto_bukti_path, 'http')) {
            return $this->foto_bukti_path;
        }

        return asset('storage/' . $this->foto_bukti_path);
    }

    // ── SCOPES ─────────────────────────────────────────────────────────

    /**
     * Scope: Tampil di public (sudah diterima/disalurkan)
     */
    public function scopePublicDisplay($query)
    {
        return $query->whereIn('status', ['diterima', 'disalurkan'])
                     ->latest('updated_at');
    }

    /**
     * Scope: Filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by user
     */
    public function scopeOfUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Search by nama_sepatu atau nama donatur
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('nama_sepatu', 'like', "%{$search}%")
            ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
    }

    /**
     * Scope: Sudah diverifikasi
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * Scope: Belum diverifikasi
     */
    public function scopePending($query)
    {
        return $query->whereNull('verified_at');
    }
}