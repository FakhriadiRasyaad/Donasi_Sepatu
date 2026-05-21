<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::withCount(['donations', 'dailyLogins as total_checkin'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->paginate($request->integer('per_page', 20));

        $users->getCollection()->transform(fn ($u) => [
            'id'             => $u->id,
            'nama'           => $u->nama,
            'email'          => $u->email,
            'role'           => $u->role,
            'is_active'      => $u->is_active,
            'avatar_url'     => $u->avatar_url,
            'total_donasi'   => $u->donations_count,
            'total_checkin'  => $u->total_checkin,
            'created_at'     => $u->created_at,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil',
            'data'    => [
                'users'      => $users->items(),
                'pagination' => [
                    'total'        => $users->total(),
                    'per_page'     => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page'    => $users->lastPage(),
                ],
            ],
        ]);
    }

    public function toggleActive(Request $request, int $id): JsonResponse
    {
        if ($id === $request->user()->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tidak bisa menonaktifkan akun sendiri.',
            ], 400);
        }

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user = User::findOrFail($id);
        $user->update(['is_active' => $request->is_active]);

        $label = $request->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'status'  => 'success',
            'message' => "User berhasil {$label}.",
            'data'    => ['id' => $id, 'is_active' => $request->is_active],
        ]);
    }
}
