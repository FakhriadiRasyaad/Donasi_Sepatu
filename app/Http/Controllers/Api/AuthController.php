<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Models\DailyLogin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ── POST /api/auth/register ───────────────────────────────────────

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'nama'     => $request->nama,
            'email'    => $request->email,
            'password' => $request->password, // auto-hashed via cast
            'role'     => 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Registrasi berhasil. Selamat datang!',
            'data'    => [
                'token' => $token,
                'user'  => $this->userResource($user),
            ],
        ], 201);
    }

    // ── POST /api/auth/login ──────────────────────────────────────────

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akun Anda telah dinonaktifkan. Hubungi admin.',
            ], 403);
        }

        // Hapus token lama, buat yang baru (single session)
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Login berhasil.',
            'data'    => [
                'token' => $token,
                'user'  => $this->userResource($user),
            ],
        ]);
    }

    // ── POST /api/auth/logout ─────────────────────────────────────────

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logout berhasil.',
        ]);
    }

    // ── GET /api/auth/me ──────────────────────────────────────────────

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        // Ambil streak minggu berjalan
        $lastCheckin = DailyLogin::where('user_id', $user->id)
            ->orderByDesc('tanggal_checkin')
            ->first();

        $mingguKe = $lastCheckin?->minggu_ke ?? 1;

        $weekCheckins = DailyLogin::where('user_id', $user->id)
            ->where('minggu_ke', $mingguKe)
            ->orderBy('hari_ke')
            ->get();

        $today        = now()->toDateString();
        $checkedToday = $weekCheckins->contains('tanggal_checkin', $today);
        $streakCount  = $weekCheckins->count();
        $isStreakDone = $streakCount >= config('app.streak_days', 7);
        $lastEntry    = $weekCheckins->last();
        $rewardClaimed= $isStreakDone && $lastEntry?->reward_claimed;

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil',
            'data'    => [
                'user'   => $this->userResource($user),
                'streak' => [
                    'minggu_ke'      => $mingguKe,
                    'hari_selesai'   => $streakCount,
                    'hari_target'    => config('app.streak_days', 7),
                    'checked_days'   => $weekCheckins->pluck('hari_ke'),
                    'checked_today'  => $checkedToday,
                    'streak_done'    => $isStreakDone,
                    'reward_claimed' => $rewardClaimed,
                ],
            ],
        ]);
    }

    // ── Shared resource format ────────────────────────────────────────

    private function userResource(User $user): array
    {
        return [
            'id'          => $user->id,
            'nama'        => $user->nama,
            'email'       => $user->email,
            'role'        => $user->role,
            'avatar_url'  => $user->avatar_url,
            'member_since'=> $user->created_at,
        ];
    }
}
