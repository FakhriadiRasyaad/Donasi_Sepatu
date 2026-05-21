<?php
// app/Http/Controllers/Api/Admin/CategoryController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * GET /api/admin/categories
     * Admin: Ambil semua kategori
     */
    public function index()
    {
        $this->authorize('admin-only');

        $categories = ArticleCategory::withCount('articles')->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * POST /api/admin/categories
     * Admin: Buat kategori baru
     */
    public function store(Request $request)
    {
        $this->authorize('admin-only');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:article_categories,name',
            'description' => 'nullable|string|max:1000',
        ]);

        $category = ArticleCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dibuat',
            'data' => $category,
        ], 201);
    }

    /**
     * PUT /api/admin/categories/{id}
     * Admin: Update kategori
     */
    public function update(Request $request, ArticleCategory $category)
    {
        $this->authorize('admin-only');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:article_categories,name,' . $category->id,
            'description' => 'nullable|string|max:1000',
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui',
            'data' => $category,
        ]);
    }

    /**
     * DELETE /api/admin/categories/{id}
     * Admin: Hapus kategori
     */
    public function destroy(ArticleCategory $category)
    {
        $this->authorize('admin-only');

        // Cek apakah ada artikel yang menggunakan kategori ini
        if ($category->articles()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa menghapus kategori yang masih memiliki artikel',
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus',
        ]);
    }

    /**
     * Helper untuk check admin
     */
    protected function authorize($action)
    {
        if ($action === 'admin-only' && auth()->user()?->role !== 'admin') {
            abort(403, 'Unauthorized');
        }
    }
}