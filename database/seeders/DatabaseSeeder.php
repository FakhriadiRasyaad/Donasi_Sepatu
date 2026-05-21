<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin default ─────────────────────────────────────────────
        DB::table('users')->insertOrIgnore([
            'nama'       => 'Super Admin',
            'email'      => 'admin@sepatudonasi.id',
            'password'   => Hash::make('Admin@2024!'),
            'role'       => 'admin',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── Konten homepage awal ──────────────────────────────────────
        $contents = [
            ['key_name' => 'visi',          'label' => 'Visi Platform',          'type' => 'text',  'sort_order' => 1,
             'value_text' => 'Menjadi jembatan kepedulian antara donatur sepatu dengan mereka yang membutuhkan, satu langkah untuk seribu harapan.'],
            ['key_name' => 'misi',          'label' => 'Misi Platform',          'type' => 'text',  'sort_order' => 2,
             'value_text' => "1. Mempermudah proses donasi sepatu layak pakai.\n2. Memastikan sepatu tersalurkan tepat sasaran.\n3. Menciptakan ekosistem donasi yang transparan dan terpercaya."],
            ['key_name' => 'hero_subtitle', 'label' => 'Hero Subtitle Text',     'type' => 'text',  'sort_order' => 0,
             'value_text' => 'Setiap langkah kebaikan dimulai dari sini.'],
            ['key_name' => 'hero_image_1',  'label' => 'Hero Carousel - Foto 1', 'type' => 'image', 'sort_order' => 1, 'value_text' => null],
            ['key_name' => 'hero_image_2',  'label' => 'Hero Carousel - Foto 2', 'type' => 'image', 'sort_order' => 2, 'value_text' => null],
            ['key_name' => 'hero_image_3',  'label' => 'Hero Carousel - Foto 3', 'type' => 'image', 'sort_order' => 3, 'value_text' => null],
        ];

        foreach ($contents as $content) {
            DB::table('homepage_contents')->insertOrIgnore(array_merge($content, [
                'is_active'  => true,
                'updated_at' => now(),
            ]));
        }

        // ── Reward contoh ─────────────────────────────────────────────
        DB::table('rewards')->insertOrIgnore([
            [
                'nama_reward'  => 'Voucher Laundry Sepatu',
                'jenis'        => 'voucher',
                'deskripsi'    => 'Dapatkan voucher gratis laundry sepatu di mitra kami senilai Rp 25.000.',
                'kode_kupon'   => 'STREAK7-LAUNDRY',
                'nilai'        => 'Rp 25.000',
                'status_aktif' => true,
                'minggu_ke'    => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'nama_reward'  => 'Free Konsultasi Perawatan',
                'jenis'        => 'konsultasi',
                'deskripsi'    => 'Sesi konsultasi online 30 menit gratis tentang cara merawat sepatu agar tahan lama.',
                'kode_kupon'   => 'STREAK7-KONSUL',
                'nilai'        => '1 sesi gratis',
                'status_aktif' => false,
                'minggu_ke'    => 2,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);
    }
}
