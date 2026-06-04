<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyLogin;
use App\Models\Reward;
use App\Models\UserReward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RewardController extends Controller
{
    private const STREAK_DAYS = 7;

    // ── GET /api/rewards ──────────────────────────────────────────────

    public function index(Request $request): JsonResponse
{
    $user = $request->user();

    // Ambil semua reward yang aktif saja (tanpa filter minggu)
    $rewards = Reward::where('status_aktif', true)
        ->get(['id', 'nama_reward', 'jenis', 'deskripsi', 'nilai', 'kode_kupon', 'berlaku_dari', 'berlaku_sampai']);

    // Cek reward mana yang sudah diklaim user ini
    $claimedRewards = UserReward::where('user_id', $user->id)
        ->get(['reward_id', 'unique_code'])
        ->keyBy('reward_id');

    $rewards = $rewards->map(function ($r) use ($claimedRewards) {
        $claimed = $claimedRewards->get($r->id);
        return [
            'id'           => $r->id,
            'nama_reward'  => $r->nama_reward,
            'jenis'        => $r->jenis,
            'deskripsi'    => $r->deskripsi,
            'nilai'        => $r->nilai,
            'sudah_diklaim'=> $claimed !== null,
            'unique_code'  => $claimed ? $claimed->unique_code : null,
        ];
    });

    return response()->json([
        'status'  => 'success',
        'message' => 'Berhasil',
        'data'    => ['rewards' => $rewards],
    ]);
}

    // ── POST /api/rewards/{id}/claim ──────────────────────────────────

    public function claim(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        // Cek streak selesai
        $streakData = DailyLogin::where('user_id', $user->id)
            ->selectRaw('minggu_ke, COUNT(*) as total')
            ->groupBy('minggu_ke')
            ->orderByDesc('minggu_ke')
            ->first();

        if (! $streakData || $streakData->total < self::STREAK_DAYS) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Streak 7 hari belum selesai. Selesaikan dulu check-in harian kamu!',
            ], 403);
        }

        $mingguKe = $streakData->minggu_ke;

        // Validasi reward
        $reward = Reward::where('id', $id)
            ->where('status_aktif', true)
            ->where('minggu_ke', $mingguKe)
            ->firstOr(fn () => null);

        if (! $reward) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Reward tidak tersedia atau tidak sesuai minggu streak kamu.',
            ], 404);
        }

        // Cek sudah diklaim
        $alreadyClaimed = UserReward::where('user_id', $user->id)
            ->where('reward_id', $id)
            ->where('minggu_ke', $mingguKe)
            ->exists();

        if ($alreadyClaimed) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu sudah mengklaim reward ini untuk minggu ini.',
            ], 409);
        }

        // Cek stok
        if ($reward->stok !== null) {
            $claimed = UserReward::where('reward_id', $id)
                ->where('minggu_ke', $mingguKe)
                ->count();

            if ($claimed >= $reward->stok) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Maaf, stok reward ini sudah habis.',
                ], 410);
            }
        }

        // Generate unique code
        $uniqueCode = 'RW-' . strtoupper(Str::random(8));

        // Transaksi: simpan klaim + update flag hari ke-7
        DB::transaction(function () use ($user, $reward, $mingguKe, $uniqueCode) {
            UserReward::create([
                'user_id'    => $user->id,
                'reward_id'  => $reward->id,
                'minggu_ke'  => $mingguKe,
                'unique_code'=> $uniqueCode,
                'claimed_at' => now(),
            ]);

            DailyLogin::where('user_id', $user->id)
                ->where('minggu_ke', $mingguKe)
                ->where('hari_ke', self::STREAK_DAYS)
                ->update(['reward_claimed' => true]);
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Selamat! Reward berhasil diklaim. Simpan kode kupon kamu! 🎉',
            'data'    => [
                'reward' => [
                    'nama'       => $reward->nama_reward,
                    'jenis'      => $reward->jenis,
                    'deskripsi'  => $reward->deskripsi,
                    'kode_kupon' => $reward->kode_kupon,
                    'nilai'      => $reward->nilai,
                    'unique_code'=> $uniqueCode,
                ],
            ],
        ]);
    }
}
