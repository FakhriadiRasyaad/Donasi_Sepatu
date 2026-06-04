<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Article::where('status', 'published')
            ->with('author:id,nama')
            ->select(['id','judul','slug','foto_path','ringkasan','kategori','published_at','author_id'])
            ->latest('published_at');

        if ($request->filled('search')) {
            $query->where('judul', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $articles = $query->paginate($request->integer('per_page', 9));

        $articles->getCollection()->transform(fn($a) => [
            'id'           => $a->id,
            'judul'        => $a->judul,
            'slug'         => $a->slug,
            'foto_url'     => $a->foto_url,
            'ringkasan'    => $a->ringkasan,
            'kategori'     => $a->kategori,
            'author'       => $a->author?->nama,
            'published_at' => $a->published_at,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil',
            'data'    => [
                'articles'   => $articles->items(),
                'pagination' => [
                    'total'        => $articles->total(),
                    'per_page'     => $articles->perPage(),
                    'current_page' => $articles->currentPage(),
                    'last_page'    => $articles->lastPage(),
                ],
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $article = Article::where('status', 'published')
            ->where('slug', $slug)
            ->with('author:id,nama')
            ->firstOrFail();

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil',
            'data'    => [
                'article' => [
                    'id'           => $article->id,
                    'judul'        => $article->judul,
                    'slug'         => $article->slug,
                    'foto_url'     => $article->foto_url,
                    'ringkasan'    => $article->ringkasan,
                    'konten'       => $article->konten,
                    'kategori'     => $article->kategori,
                    'author'       => $article->author?->nama,
                    'published_at' => $article->published_at,
                ],
            ],
        ]);
    }
}