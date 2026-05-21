<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\HomepageContent;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    // ── GET /api/content/homepage (public) ────────────────────────────

    public function homepage(): JsonResponse
    {
        $contents = HomepageContent::active()->get();

        $heroImages = $contents
            ->filter(fn ($c) => $c->type === 'image' && str_starts_with($c->key_name, 'hero_image') && $c->value_text)
            ->map(fn ($c) => ['key' => $c->key_name, 'url' => $c->preview_url])
            ->values();

        $texts = $contents
            ->filter(fn ($c) => $c->type !== 'image')
            ->keyBy('key_name')
            ->map(fn ($c) => $c->value_text);

        // 4 sepatu untuk display homepage
        $displaySepatu = Donation::publicDisplay()
            ->with('user:id,nama')
            ->limit(4)
            ->get()
            ->map(fn ($d) => [
                'id'           => $d->id,
                'nama_sepatu'  => $d->nama_sepatu,
                'ukuran'       => $d->ukuran,
                'kondisi'      => $d->kondisi,
                'foto_url'     => $d->foto_url,
                'status'       => $d->status,
                'nama_donatur' => $d->user->nama,
                'created_at'   => $d->created_at,
            ]);

        // Statistik ringkas
        $stats = Donation::selectRaw("
            COUNT(*) as total_donasi,
            SUM(status = 'disalurkan') as total_disalurkan,
            SUM(status = 'diterima')   as total_diterima,
            SUM(status = 'pending')    as total_pending
        ")->first();

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil',
            'data'    => [
                'hero_images'    => $heroImages,
                'visi'           => $texts['visi']          ?? null,
                'misi'           => $texts['misi']          ?? null,
                'hero_subtitle'  => $texts['hero_subtitle'] ?? null,
                'display_sepatu' => $displaySepatu,
                'statistik'      => [
                    'total_donasi'     => (int) $stats->total_donasi,
                    'total_disalurkan' => (int) $stats->total_disalurkan,
                    'total_diterima'   => (int) $stats->total_diterima,
                    'total_pending'    => (int) $stats->total_pending,
                ],
            ],
        ]);
    }
}
