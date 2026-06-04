<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Article::with('author:id,nama')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('judul', 'like', '%' . $request->search . '%');
        }

        $articles = $query->paginate($request->integer('per_page', 15));

        $articles->getCollection()->transform(fn($a) => [
            'id'           => $a->id,
            'judul'        => $a->judul,
            'slug'         => $a->slug,
            'foto_url'     => $a->foto_url,
            'ringkasan'    => $a->ringkasan,
            'kategori'     => $a->kategori,
            'status'       => $a->status,
            'author'       => $a->author?->nama,
            'published_at' => $a->published_at,
            'created_at'   => $a->created_at,
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

    public function show(int $id): JsonResponse
    {
        $article = Article::with('author:id,nama')->findOrFail($id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil',
            'data'    => [
                'article' => array_merge($article->toArray(), [
                    'foto_url' => $article->foto_url,
                ]),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'judul'     => ['required', 'string', 'max:200'],
            'ringkasan' => ['nullable', 'string', 'max:500'],
            'konten'    => ['required', 'string'],
            'kategori'  => ['nullable', 'string', 'max:50'],
            'status'    => ['in:draft,published'],
            'foto'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('articles', 'public');
        }

        $article = Article::create([
            'judul'        => $data['judul'],
            'slug'         => Article::generateSlug($data['judul']),
            'ringkasan'    => $data['ringkasan'] ?? null,
            'konten'       => $data['konten'],
            'kategori'     => $data['kategori'] ?? 'umum',
            'status'       => $data['status'] ?? 'draft',
            'foto_path'    => $fotoPath,
            'author_id'    => $request->user()->id,
            'published_at' => ($data['status'] ?? 'draft') === 'published' ? now() : null,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Artikel berhasil dibuat.',
            'data'    => ['id' => $article->id, 'slug' => $article->slug],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $article = Article::findOrFail($id);

        $data = $request->validate([
            'judul'     => ['sometimes', 'string', 'max:200'],
            'ringkasan' => ['nullable', 'string', 'max:500'],
            'konten'    => ['sometimes', 'string'],
            'kategori'  => ['nullable', 'string', 'max:50'],
            'status'    => ['in:draft,published'],
            'foto'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('foto')) {
            if ($article->foto_path) {
                Storage::disk('public')->delete($article->foto_path);
            }
            $data['foto_path'] = $request->file('foto')->store('articles', 'public');
        }
        unset($data['foto']);

        if (isset($data['status']) && $data['status'] === 'published' && !$article->published_at) {
            $data['published_at'] = now();
        }

        if (isset($data['judul']) && $data['judul'] !== $article->judul) {
            $data['slug'] = Article::generateSlug($data['judul']);
        }

        $article->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Artikel berhasil diperbarui.',
            'data'    => ['id' => $id, 'slug' => $article->fresh()->slug],
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $article = Article::findOrFail($id);

        if ($article->foto_path) {
            Storage::disk('public')->delete($article->foto_path);
        }
        $article->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Artikel berhasil dihapus.',
        ]);
    }
}