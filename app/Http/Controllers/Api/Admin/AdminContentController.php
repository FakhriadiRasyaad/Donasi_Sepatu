<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminContentController extends Controller
{
    // ── GET /api/admin/content ────────────────────────────────────────

    public function index(): JsonResponse
    {
        $contents = HomepageContent::orderBy('sort_order')->get()
            ->map(fn ($c) => [
                'id'          => $c->id,
                'key_name'    => $c->key_name,
                'label'       => $c->label,
                'type'        => $c->type,
                'value_text'  => $c->value_text,
                'preview_url' => $c->preview_url,
                'is_active'   => $c->is_active,
                'sort_order'  => $c->sort_order,
                'updated_at'  => $c->updated_at,
            ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil',
            'data'    => ['contents' => $contents],
        ]);
    }

    // ── PUT /api/admin/content/{key} — update teks ────────────────────

    public function updateText(Request $request, string $key): JsonResponse
    {
        $request->validate([
            'value_text' => ['nullable', 'string'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $content = HomepageContent::where('key_name', $key)->firstOrFail();

        if ($content->type === 'image') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gunakan endpoint POST /admin/content/image untuk konten bertipe gambar.',
            ], 400);
        }

        $content->update(array_filter([
            'value_text' => $request->value_text,
            'is_active'  => $request->is_active,
            'updated_by' => $request->user()->id,
        ], fn ($v) => $v !== null));

        return response()->json([
            'status'  => 'success',
            'message' => 'Konten berhasil diperbarui.',
            'data'    => ['key_name' => $key],
        ]);
    }

    // ── POST /api/admin/content/image — upload/replace hero image ─────

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'key_name' => ['required', 'string', 'max:100'],
            'label'    => ['nullable', 'string', 'max:150'],
            'gambar'   => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'key_name.required' => 'key_name tidak boleh kosong.',
            'gambar.required'   => 'File gambar wajib di-upload.',
            'gambar.max'        => 'Ukuran gambar maksimal 5MB.',
        ]);

        $existing = HomepageContent::where('key_name', $request->key_name)->first();

        // Hapus gambar lama
        if ($existing?->value_text) {
            Storage::disk('public')->delete($existing->value_text);
        }

        $newPath = $request->file('gambar')->store('hero_images', 'public');

        if ($existing) {
            $existing->update([
                'value_text' => $newPath,
                'is_active'  => true,
                'updated_by' => $request->user()->id,
            ]);
        } else {
            $maxOrder = HomepageContent::where('type', 'image')->max('sort_order') ?? 0;
            HomepageContent::create([
                'key_name'   => $request->key_name,
                'label'      => $request->label ?? $request->key_name,
                'type'       => 'image',
                'value_text' => $newPath,
                'is_active'  => true,
                'sort_order' => $maxOrder + 1,
                'updated_by' => $request->user()->id,
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Gambar berhasil diupload.',
            'data'    => [
                'key_name'    => $request->key_name,
                'preview_url' => asset('storage/' . $newPath),
            ],
        ], 201);
    }
}
