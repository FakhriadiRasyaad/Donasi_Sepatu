<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Donation\StoreDonationRequest;
use App\Models\Donation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DonationController extends Controller
{
    // ── GET /api/donations ────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = Donation::where('user_id', $request->user()->id)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $donations = $query->paginate($request->integer('per_page', 10));

        $donations->getCollection()->transform(fn ($d) => $this->donationResource($d));

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil',
            'data'    => [
                'donations'  => $donations->items(),
                'pagination' => [
                    'total'        => $donations->total(),
                    'per_page'     => $donations->perPage(),
                    'current_page' => $donations->currentPage(),
                    'last_page'    => $donations->lastPage(),
                ],
            ],
        ]);
    }

    // ── POST /api/donations ───────────────────────────────────────────

    public function store(StoreDonationRequest $request): JsonResponse
    {
        $fotoPath = $request->file('foto_sepatu')
            ->store('donations', 'public');

        $donation = Donation::create([
            'user_id'           => $request->user()->id,
            'nama_sepatu'       => $request->nama_sepatu,
            'ukuran'            => $request->ukuran,
            'kondisi'           => $request->kondisi,
            'deskripsi'         => $request->deskripsi,
            'foto_path'         => $fotoPath,
            'metode_pengiriman' => $request->metode_pengiriman,
            'nama_ekspedisi'    => $request->metode_pengiriman === 'ekspedisi'
                                    ? $request->nama_ekspedisi
                                    : null,
            'status'            => 'pending',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Donasi sepatu berhasil dikirim. Terima kasih atas kebaikanmu!',
            'data'    => ['donation' => $this->donationResource($donation)],
        ], 201);
    }

    // ── GET /api/donations/{id} ───────────────────────────────────────

    public function show(Request $request, int $id): JsonResponse
    {
        $donation = Donation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil',
            'data'    => ['donation' => $this->donationResource($donation)],
        ]);
    }

    // ── Resource format ───────────────────────────────────────────────

    private function donationResource(Donation $d): array
    {
        return [
            'id'                => $d->id,
            'nama_sepatu'       => $d->nama_sepatu,
            'ukuran'            => $d->ukuran,
            'kondisi'           => $d->kondisi,
            'deskripsi'         => $d->deskripsi,
            'foto_url'          => $d->foto_url,
            'metode_pengiriman' => $d->metode_pengiriman,
            'nama_ekspedisi'    => $d->nama_ekspedisi,
            'no_resi'           => $d->no_resi,
            'status'            => $d->status,
            'catatan_admin'     => $d->catatan_admin,
            'created_at'        => $d->created_at,
            'updated_at'        => $d->updated_at,
        ];
    }
}
