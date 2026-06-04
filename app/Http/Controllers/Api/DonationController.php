<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Donation\StoreDonationRequest;
use App\Models\Donation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    // ── GET /api/donations ────────────────────────────────────────────
    /**
     * List donasi user sendiri
     */
    public function index(Request $request): JsonResponse
    {
        $query = Donation::where('user_id', $request->user()->id)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $donations = $query->paginate($request->integer('per_page', 10));

        $donations->getCollection()->transform(fn($d) => $this->donationResource($d));

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil',
            'data' => [
                'donations' => $donations->items(),
                'pagination' => [
                    'total' => $donations->total(),
                    'per_page' => $donations->perPage(),
                    'current_page' => $donations->currentPage(),
                    'last_page' => $donations->lastPage(),
                ],
            ],
        ]);
    }

    // ── POST /api/donations ───────────────────────────────────────────
    /**
     * Create donasi baru
     */
    public function store(StoreDonationRequest $request): JsonResponse
    {
        $fotoPath = $request->file('foto_sepatu')
            ->store('donations', 'public');

        $donation = Donation::create([
            'user_id' => $request->user()->id,
            'nama_sepatu' => $request->nama_sepatu,
            'ukuran' => $request->ukuran,
            'kondisi' => $request->kondisi,
            'deskripsi' => $request->deskripsi,
            'foto_path' => $fotoPath,
            'metode_pengiriman' => $request->metode_pengiriman,
            'nama_ekspedisi' => $request->metode_pengiriman === 'ekspedisi'
                ? $request->nama_ekspedisi
                : null,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Donasi sepatu berhasil dikirim. Terima kasih atas kebaikanmu!',
            'data' => ['donation' => $this->donationResource($donation)],
        ], 201);
    }

    // ── GET /api/donations/{id} ───────────────────────────────────────
    /**
     * Detail donasi user
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $donation = Donation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil',
            'data' => ['donation' => $this->donationResource($donation)],
        ]);
    }

    // ────────────────────────────────────────────────────────────────────
    // ADMIN ENDPOINTS
    // ────────────────────────────────────────────────────────────────────

    // ── GET /api/admin/donations ──────────────────────────────────────
    /**
     * List semua donasi (admin only)
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $this->authorize('isAdmin', Donation::class);

        $query = Donation::latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by nama sepatu atau nama donatur
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('nama_sepatu', 'like', "%{$search}%")
                ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $donations = $query->paginate($request->integer('per_page', 15));

        $donations->getCollection()->transform(fn($d) => $this->adminDonationResource($d));

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil',
            'data' => [
                'donations' => $donations->items(),
                'pagination' => [
                    'total' => $donations->total(),
                    'per_page' => $donations->perPage(),
                    'current_page' => $donations->currentPage(),
                    'last_page' => $donations->lastPage(),
                ],
            ],
        ]);
    }

    // ── PATCH /api/admin/donations/{id} ───────────────────────────────
    /**
     * Update donasi (harga, status, catatan)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorize('isAdmin', Donation::class);

        $request->validate([
            'harga' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:pending,diterima,disalurkan,ditolak',
            'catatan_admin' => 'nullable|string|max:500',
            'no_resi' => 'nullable|string|max:100',
        ]);

        $donation = Donation::findOrFail($id);

        $updateData = [];

        // Update harga jika ada
       if ($request->has('harga') && $request->harga !== null && $request->harga !== '') {
    $hargaValue = (int)$request->harga;
    $updateData['harga'] = $hargaValue;
}

        // Update status jika ada
        if ($request->filled('status')) {
            $updateData['status'] = $request->status;
            // Set verified_by dan verified_at jika status berubah
            if ($request->status !== 'pending') {
                $updateData['verified_by'] = auth()->id();
                $updateData['verified_at'] = now();
            }
        }

        // Update catatan jika ada
        if ($request->filled('catatan_admin')) {
            $updateData['catatan_admin'] = $request->catatan_admin;
        }

        // Update no_resi jika ada
        if ($request->filled('no_resi')) {
            $updateData['no_resi'] = $request->no_resi;
        }

        $donation->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Donasi berhasil diperbarui',
            'data' => ['donation' => $this->adminDonationResource($donation)],
        ]);
    }

    // ── DELETE /api/admin/donations/{id} ──────────────────────────────
    /**
     * Delete donasi (admin only)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorize('isAdmin', Donation::class);

        $donation = Donation::findOrFail($id);

        // Delete foto jika ada
        if ($donation->foto_path) {
            \Storage::disk('public')->delete($donation->foto_path);
        }

        $donation->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Donasi berhasil dihapus',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────
    // RESOURCE METHODS
    // ────────────────────────────────────────────────────────────────────

    /**
     * Format resource untuk user
     */
    private function donationResource(Donation $d): array
    {
        return [
            'id' => $d->id,
            'nama_sepatu' => $d->nama_sepatu,
            'ukuran' => $d->ukuran,
            'kondisi' => $d->kondisi,
            'harga' => $d->harga,
            'deskripsi' => $d->deskripsi,
            'foto_url' => $d->foto_url,
            'metode_pengiriman' => $d->metode_pengiriman,
            'nama_ekspedisi' => $d->nama_ekspedisi,
            'no_resi' => $d->no_resi,
            'status' => $d->status,
            'catatan_admin' => $d->catatan_admin,
            'created_at' => $d->created_at,
            'updated_at' => $d->updated_at,
        ];
    }

    /**
     * Format resource untuk admin
     */
    private function adminDonationResource(Donation $d): array
    {
        return [
            'id' => $d->id,
            'user_id' => $d->user_id,
            'nama_donatur' => $d->user?->name ?? 'Unknown',
            'email_donatur' => $d->user?->email ?? '-',
            'nama_sepatu' => $d->nama_sepatu,
            'ukuran' => $d->ukuran,
            'kondisi' => $d->kondisi,
            'harga' => $d->harga,
            'deskripsi' => $d->deskripsi,
            'foto_url' => $d->foto_url,
            'metode_pengiriman' => $d->metode_pengiriman,
            'nama_ekspedisi' => $d->nama_ekspedisi,
            'no_resi' => $d->no_resi,
            'status' => $d->status,
            'catatan_admin' => $d->catatan_admin,
            'diverifikasi_oleh' => $d->verifiedBy?->name ?? null,
            'verified_at' => $d->verified_at,
            'created_at' => $d->created_at,
            'updated_at' => $d->updated_at,
        ];
    }
}