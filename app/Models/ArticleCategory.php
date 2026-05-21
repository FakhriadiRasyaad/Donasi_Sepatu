<?php
// app/Models/ArticleCategory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ArticleCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (!$category->slug) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            $category->slug = Str::slug($category->name);
        });
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'category_id');
    }
}

// app/Models/Tag.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = ['name', 'slug'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (!$tag->slug) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::updating(function ($tag) {
            $tag->slug = Str::slug($tag->name);
        });
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_tag');
    }
}