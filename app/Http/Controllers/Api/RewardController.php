<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyLogin;
use App\Models\Reward;
use App\Models\UserReward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RewardController extends Controller
{
    private const STREAK_DAYS = 7;

    // ── GET /api/rewards ──────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $last = DailyLogin::where('user_id', $user->id)
            ->orderByDesc('tanggal_checkin')->first();

        $mingguKe = $last?->minggu_ke ?? 1;

        $rewards = Reward::active()
            ->where('minggu_ke', $mingguKe)
            ->get(['id', 'nama_reward', 'jenis', 'deskripsi', 'nilai', 'berlaku_dari', 'berlaku_sampai']);

        // Tandai reward yang sudah diklaim
        $claimedIds = UserReward::where('user_id', $user->id)
            ->where('minggu_ke', $mingguKe)
            ->pluck('reward_id');

        $rewards = $rewards->map(fn ($r) => array_merge($r->toArray(), [
            'sudah_diklaim' => $claimedIds->contains($r->id),
        ]));

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil',
            'data'    => [
                'minggu_ke' => $mingguKe,
                'rewards'   => $rewards,
            ],
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

        // Transaksi: simpan klaim + update flag hari ke-7
        DB::transaction(function () use ($user, $reward, $mingguKe) {
            UserReward::create([
                'user_id'   => $user->id,
                'reward_id' => $reward->id,
                'minggu_ke' => $mingguKe,
                'claimed_at'=> now(),
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
                ],
            ],
        ]);
    }
}
