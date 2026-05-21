<?php
// app/Models/Article.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // Event untuk generate slug otomatis
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (!$article->slug) {
                $article->slug = Str::slug($article->title);
            }
        });

        static::updating(function ($article) {
            $article->slug = Str::slug($article->title);
        });
    }

    // Relasi dengan User (author)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi dengan Category
    public function category()
    {
        return $this->belongsTo(ArticleCategory::class);
    }

    // Relasi dengan Tags (many-to-many)
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'article_tag');
    }

    // Scope untuk artikel yang published
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    // Scope untuk artikel draft
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // Increment views
    public function incrementViews()
    {
        $this->increment('views');
    }
}