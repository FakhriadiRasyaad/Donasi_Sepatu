<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    protected $fillable = [
        'nama_reward',
        'jenis',
        'deskripsi',
        'kode_kupon',
        'nilai',
        'status_aktif',
        'minggu_ke',
        'berlaku_dari',
        'berlaku_sampai',
        'stok',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status_aktif'   => 'boolean',
            'minggu_ke'      => 'integer',
            'stok'           => 'integer',
            'berlaku_dari'   => 'date',
            'berlaku_sampai' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function userRewards(): HasMany
    {
        return $this->hasMany(UserReward::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status_aktif', true);
    }

    public function getTotalDiklaimAttribute(): int
    {
        return $this->userRewards()->count();
    }
}
