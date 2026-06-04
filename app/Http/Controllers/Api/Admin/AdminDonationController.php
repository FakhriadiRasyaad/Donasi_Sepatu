<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'harga'             => $d->harga,
            'deskripsi'         => $d->deskripsi,
            'foto_url'          => $d->foto_url,
            'foto_bukti_url'    => $d->foto_bukti_url,
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
            'harga'         => ['nullable', 'numeric', 'min:0'],
            'catatan_admin' => ['nullable', 'string', 'max:1000'],
            'no_resi'       => ['nullable', 'string', 'max:100'],
        ], [
            'status.required' => 'Status tidak boleh kosong.',
            'status.in'       => 'Nilai status tidak valid.',
        ]);

        $donation = Donation::findOrFail($id);

        $updateData = [
            'status'        => $request->status,
            'catatan_admin' => $request->catatan_admin,
            'no_resi'       => $request->no_resi,
            'verified_by'   => $request->user()->id,
            'verified_at'   => now(),
        ];

        // Update harga jika dikirim
        if ($request->has('harga') && $request->harga !== null && $request->harga !== '') {
            $updateData['harga'] = (int) $request->harga;
        }

        $donation->update($updateData);

        return response()->json([
            'status'  => 'success',
            'message' => "Donasi berhasil diperbarui.",
            'data'    => [
                'id'     => $donation->id,
                'status' => $donation->status,
                'harga'  => $donation->harga,
            ],
        ]);
    }

    // ── POST /api/admin/donations/{id}/update ────────────────────────
    // Endpoint khusus untuk update dengan file upload (multipart/form-data)

    public function updateWithPhoto(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status'        => ['required', 'in:pending,diterima,disalurkan,ditolak'],
            'harga'         => ['nullable', 'numeric', 'min:0'],
            'catatan_admin' => ['nullable', 'string', 'max:1000'],
            'no_resi'       => ['nullable', 'string', 'max:100'],
            'foto_bukti'    => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ], [
            'status.required'  => 'Status tidak boleh kosong.',
            'status.in'        => 'Nilai status tidak valid.',
            'foto_bukti.image' => 'File harus berupa gambar.',
            'foto_bukti.mimes' => 'Format gambar: jpeg, jpg, png, webp.',
            'foto_bukti.max'   => 'Ukuran foto maksimal 2MB.',
        ]);

        $donation = Donation::findOrFail($id);

        $updateData = [
            'status'        => $request->status,
            'catatan_admin' => $request->catatan_admin,
            'no_resi'       => $request->no_resi,
            'verified_by'   => $request->user()->id,
            'verified_at'   => now(),
        ];

        // Update harga jika dikirim
        if ($request->has('harga') && $request->harga !== null && $request->harga !== '') {
            $updateData['harga'] = (int) $request->harga;
        }

        // Handle upload foto bukti
        if ($request->hasFile('foto_bukti')) {
            // Hapus foto lama jika ada
            if ($donation->foto_bukti_path) {
                Storage::disk('public')->delete($donation->foto_bukti_path);
            }

            $path = $request->file('foto_bukti')->store('donations/bukti', 'public');
            $updateData['foto_bukti_path'] = $path;
        }

        $donation->update($updateData);
        $donation->refresh();

        return response()->json([
            'status'  => 'success',
            'message' => 'Donasi berhasil diperbarui.',
            'data'    => [
                'id'             => $donation->id,
                'status'         => $donation->status,
                'harga'          => $donation->harga,
                'foto_bukti_url' => $donation->foto_bukti_url,
            ],
        ]);
    }
}
