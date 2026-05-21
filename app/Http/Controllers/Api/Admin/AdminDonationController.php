<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDonationController extends Controller
{
    // ── GET /api/admin/donations ──────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = Donation::with(['user:id,nama,email', 'verifiedBy:id,nama'])
            ->orderByRaw("FIELD(status, 'pending', 'diterima', 'disalurkan', 'ditolak')")
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_sepatu', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('nama', 'like', "%{$search}%"));
            });
        }

        $donations = $query->paginate($request->integer('per_page', 15));

        $donations->getCollection()->transform(fn ($d) => [
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
            'nama_donatur'      => $d->user?->nama,
            'email_donatur'     => $d->user?->email,
            'diverifikasi_oleh' => $d->verifiedBy?->nama,
            'verified_at'       => $d->verified_at,
            'created_at'        => $d->created_at,
        ]);

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

    // ── PATCH /api/admin/donations/{id} ──────────────────────────────

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status'        => ['required', 'in:pending,diterima,disalurkan,ditolak'],
            'catatan_admin' => ['nullable', 'string', 'max:1000'],
            'no_resi'       => ['nullable', 'string', 'max:100'],
        ], [
            'status.required' => 'Status tidak boleh kosong.',
            'status.in'       => 'Nilai status tidak valid.',
        ]);

        $donation = Donation::findOrFail($id);

        $donation->update([
            'status'        => $request->status,
            'catatan_admin' => $request->catatan_admin,
            'no_resi'       => $request->no_resi,
            'verified_by'   => $request->user()->id,
            'verified_at'   => now(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => "Status donasi berhasil diubah menjadi '{$request->status}'.",
            'data'    => ['id' => $id, 'status' => $request->status],
        ]);
    }
}
