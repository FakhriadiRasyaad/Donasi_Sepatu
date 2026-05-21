<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRewardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Reward::withCount('userRewards as total_diklaim')
            ->with('creator:id,nama')
            ->latest();

        if ($request->boolean('aktif')) {
            $query->where('status_aktif', true);
        }

        $rewards = $query->get();

        return response()->json([
            'status' => 'success', 'message' => 'Berhasil',
            'data'   => ['rewards' => $rewards],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama_reward'    => ['required', 'string', 'max:150'],
            'jenis'          => ['required', 'in:voucher,diskon,konsultasi,lainnya'],
            'deskripsi'      => ['required', 'string'],
            'kode_kupon'     => ['nullable', 'string', 'max:50'],
            'nilai'          => ['nullable', 'string', 'max:50'],
            'status_aktif'   => ['boolean'],
            'minggu_ke'      => ['required', 'integer', 'min:1'],
            'berlaku_dari'   => ['nullable', 'date'],
            'berlaku_sampai' => ['nullable', 'date', 'after_or_equal:berlaku_dari'],
            'stok'           => ['nullable', 'integer', 'min:1'],
        ], [
            'nama_reward.required' => 'Nama reward tidak boleh kosong.',
            'jenis.required'       => 'Jenis reward tidak boleh kosong.',
            'jenis.in'             => 'Jenis tidak valid.',
            'deskripsi.required'   => 'Deskripsi tidak boleh kosong.',
            'minggu_ke.required'   => 'Minggu ke tidak boleh kosong.',
        ]);

        $reward = Reward::create(array_merge($data, ['created_by' => $request->user()->id]));

        return response()->json([
            'status'  => 'success',
            'message' => 'Reward berhasil ditambahkan.',
            'data'    => ['id' => $reward->id],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $reward = Reward::findOrFail($id);

        $data = $request->validate([
            'nama_reward'    => ['sometimes', 'string', 'max:150'],
            'jenis'          => ['sometimes', 'in:voucher,diskon,konsultasi,lainnya'],
            'deskripsi'      => ['sometimes', 'string'],
            'kode_kupon'     => ['nullable', 'string', 'max:50'],
            'nilai'          => ['nullable', 'string', 'max:50'],
            'status_aktif'   => ['boolean'],
            'minggu_ke'      => ['sometimes', 'integer', 'min:1'],
            'berlaku_dari'   => ['nullable', 'date'],
            'berlaku_sampai' => ['nullable', 'date'],
            'stok'           => ['nullable', 'integer', 'min:1'],
        ]);

        $reward->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Reward berhasil diperbarui.',
            'data'    => ['id' => $id],
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $reward = Reward::findOrFail($id);
        $reward->update(['status_aktif' => false]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Reward berhasil dinonaktifkan.',
            'data'    => ['id' => $id],
        ]);
    }
}
