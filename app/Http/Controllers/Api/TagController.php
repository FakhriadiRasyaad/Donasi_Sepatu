<?php
// app/Http/Controllers/Api/Admin/TagController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * GET /api/admin/tags
     * Admin: Ambil semua tags
     */
    public function index()
    {
        $this->authorize('admin-only');

        $tags = Tag::withCount('articles')->get();

        return response()->json([
            'success' => true,
            'data' => $tags,
        ]);
    }

    /**
     * POST /api/admin/tags
     * Admin: Buat tag baru
     */
    public function store(Request $request)
    {
        $this->authorize('admin-only');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
        ]);

        $tag = Tag::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tag berhasil dibuat',
            'data' => $tag,
        ], 201);
    }

    /**
     * PUT /api/admin/tags/{id}
     * Admin: Update tag
     */
    public function update(Request $request, Tag $tag)
    {
        $this->authorize('admin-only');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name,' . $tag->id,
        ]);

        $tag->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tag berhasil diperbarui',
            'data' => $tag,
        ]);
    }

    /**
     * DELETE /api/admin/tags/{id}
     * Admin: Hapus tag
     */
    public function destroy(Tag $tag)
    {
        $this->authorize('admin-only');

        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tag berhasil dihapus',
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