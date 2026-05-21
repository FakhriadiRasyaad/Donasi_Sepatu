<?php
// app/Http/Controllers/Api/ArticleController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * GET /api/articles
     * Ambil semua artikel yang published dengan pagination
     */
    public function index(Request $request)
    {
        $query = Article::published()
            ->with(['user', 'category', 'tags'])
            ->orderBy('published_at', 'desc');

        // Filter by category
        if ($request->has('category')) {
            $query->whereHas('category', function ($q) {
                $q->where('slug', request('category'));
            });
        }

        // Filter by tag
        if ($request->has('tag')) {
            $query->whereHas('tags', function ($q) {
                $q->where('slug', request('tag'));
            });
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
        }

        $articles = $query->paginate($request->get('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => $articles,
        ]);
    }

    /**
     * GET /api/articles/{slug}
     * Ambil detail artikel berdasarkan slug dan increment views
     */
    public function show($slug)
    {
        $article = Article::where('slug', $slug)
            ->published()
            ->with(['user', 'category', 'tags'])
            ->firstOrFail();

        // Increment views
        $article->incrementViews();

        // Ambil artikel related (dari kategori yang sama)
        $relatedArticles = Article::published()
            ->where('category_id', $article->category_id)
            ->where('id', '!=', $article->id)
            ->limit(3)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $article,
            'related' => $relatedArticles,
        ]);
    }

    /**
     * GET /api/articles/categories
     * Ambil semua kategori
     */
    public function getCategories()
    {
        $categories = \App\Models\ArticleCategory::withCount('articles')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * GET /api/articles/tags
     * Ambil semua tags
     */
    public function getTags()
    {
        $tags = \App\Models\Tag::withCount('articles')->get();

        return response()->json([
            'success' => true,
            'data' => $tags,
        ]);
    }
}