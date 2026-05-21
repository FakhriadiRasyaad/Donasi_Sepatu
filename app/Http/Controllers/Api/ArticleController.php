<?php
// app/Models/ArticleCategory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ArticleCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description'];
    protected $table = 'article_categories';

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