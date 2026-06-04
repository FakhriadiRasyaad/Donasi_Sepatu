<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Article extends Model
{
    protected $fillable = [
        'judul',
        'slug',
        'foto_path',
        'ringkasan',
        'konten',
        'kategori',
        'status',
        'author_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public static function generateSlug(string $judul): string
    {
        $base  = Str::slug($judul);
        $slug  = $base;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $count;
            $count++;
        }

        return $slug;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_path ? asset('storage/' . $this->foto_path) : null;
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->latest('published_at');
    }
}