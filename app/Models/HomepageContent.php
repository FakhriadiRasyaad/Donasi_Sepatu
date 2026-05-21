<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageContent extends Model
{
    protected $table = 'homepage_contents';

    // updated_at dikelola manual via touch(), created_at tidak ada
    const UPDATED_AT = 'updated_at';
    const CREATED_AT = null;

    protected $fillable = [
        'key_name',
        'label',
        'type',
        'value_text',
        'is_active',
        'sort_order',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getPreviewUrlAttribute(): ?string
    {
        if ($this->type === 'image' && $this->value_text) {
            return asset('storage/' . $this->value_text);
        }
        return null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
