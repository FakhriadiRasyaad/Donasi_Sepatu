<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyLogin;
use App\Models\Reward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckinController extends Controller
{
    private const STREAK_DAYS = 7;

    // ── POST /api/checkin ─────────────────────────────────────────────

    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'foto_sepatu' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'foto_sepatu.required' => 'Foto sepatu wajib di-upload untuk check-in.',
            'foto_sepatu.image'    => 'File harus berupa gambar.',
            'foto_sepatu.max'      => 'Ukuran foto maksimal 5MB.',
        ]);

        $user  = $request->user();
        $today = now()->toDateString();

        // Cek sudah check-in hari ini
        $alreadyCheckin = DailyLogin::where('user_id', $user->id)
            ->where('tanggal_checkin', $today)
            ->exists();

        if ($alreadyCheckin) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kamu sudah check-in hari ini. Kembali besok ya!',
            ], 409);
        }

        // ── Hitung posisi streak ──────────────────────────────────────
        $last = DailyLogin::where('user_id', $user->id)
            ->orderByDesc('tanggal_checkin')
            ->first();

        if (! $last) {
            $mingguKe = 1;
            $hariKe   = 1;
        } else {
            $diffDays = now()->startOfDay()
                ->diffInDays(\Carbon\Carbon::parse($last->tanggal_checkin)->startOfDay());

            if ($last->hari_ke >= self::STREAK_DAYS) {
                // Minggu sebelumnya selesai → mulai minggu baru
                $mingguKe = $last->minggu_ke + 1;
                $hariKe   = 1;
            } elseif ($diffDays === 1) {
                // Hari berturut-turut
                $mingguKe = $last->minggu_ke;
                $hariKe   = $last->hari_ke + 1;
            } else {
                // Streak putus → reset ke minggu baru
                $mingguKe = $last->minggu_ke + 1;
                $hariKe   = 1;
            }
        }

        // ── Upload foto ───────────────────────────────────────────────
        $fotoPath = $request->file('foto_sepatu')
            ->store('daily_checkin', 'public');

        // ── Simpan check-in ───────────────────────────────────────────
        DailyLogin::create([
            'user_id'          => $user->id,
            'tanggal_checkin'  => $today,
            'foto_sepatu_path' => $fotoPath,
            'minggu_ke'        => $mingguKe,
            'hari_ke'          => $hariKe,
            'reward_claimed'   => false,
        ]);

        $isStreakDone = ($hariKe === self::STREAK_DAYS);

        return response()->json([
            'status'  => 'success',
            'message' => $isStreakDone
                ? 'Selamat! Streak 7 hari selesai! Klaim reward kamu sekarang 🎉'
                : "Check-in hari ke-{$hariKe} berhasil! Semangat terus!",
            'data' => [
                'checkin' => [
                    'tanggal'     => $today,
                    'hari_ke'     => $hariKe,
                    'minggu_ke'   => $mingguKe,
                    'foto_url'    => asset('storage/' . $fotoPath),
                    'streak_done' => $isStreakDone,
                    'hari_tersisa'=> self::STREAK_DAYS - $hariKe,
                ],
            ],
        ], 201);
    }

    // ── GET /api/checkin/status ───────────────────────────────────────

    public function status(Request $request): JsonResponse
    {
        $user  = $request->user();
        $today = now()->toDateString();

        $last = DailyLogin::where('user_id', $user->id)
            ->orderByDesc('tanggal_checkin')
            ->first();

        $mingguKe = $last?->minggu_ke ?? 1;

        $weekCheckins = DailyLogin::where('user_id', $user->id)
            ->where('minggu_ke', $mingguKe)
            ->orderBy('hari_ke')
            ->get();

        // Bangun kalender 7 slot
        $checkinMap = $weekCheckins->keyBy('hari_ke');
        $calendar = collect(range(1, self::STREAK_DAYS))->map(function (int $day) use ($checkinMap): array {
            $entry = $checkinMap->get($day);
            return [
                'hari'     => $day,
                'done'     => (bool) $entry,
                'tanggal'  => $entry?->tanggal_checkin?->toDateString(),
                'foto_url' => $entry?->foto_url,
            ];
        });

        $streakCount   = $weekCheckins->count();
        $isStreakDone  = $streakCount >= self::STREAK_DAYS;
        $lastEntry     = $weekCheckins->last();
        $rewardClaimed = $isStreakDone && $lastEntry?->reward_claimed;
        $checkedToday  = $weekCheckins->contains('tanggal_checkin', $today);

        // Reward aktif minggu ini
        $activeReward = Reward::active()
            ->where('minggu_ke', $mingguKe)
            ->first(['id', 'nama_reward', 'jenis', 'deskripsi', 'nilai']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil',
            'data'    => [
                'minggu_ke'      => $mingguKe,
                'hari_selesai'   => $streakCount,
                'hari_target'    => self::STREAK_DAYS,
                'checked_today'  => $checkedToday,
                'streak_done'    => $isStreakDone,
                'reward_claimed' => $rewardClaimed,
                'calendar'       => $calendar,
                'active_reward'  => $activeReward,
            ],
        ]);
    }
}
