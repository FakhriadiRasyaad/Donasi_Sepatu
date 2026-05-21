<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_logins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal_checkin');
            $table->string('foto_sepatu_path', 255);
            $table->unsignedInteger('minggu_ke');
            $table->unsignedTinyInteger('hari_ke');
            $table->boolean('reward_claimed')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'tanggal_checkin'], 'uq_user_tanggal');
            $table->index(['user_id', 'minggu_ke']);
            $table->index('tanggal_checkin');
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('nama_reward', 150);
            $table->enum('jenis', ['voucher', 'diskon', 'konsultasi', 'lainnya'])->default('voucher');
            $table->text('deskripsi');
            $table->string('kode_kupon', 50)->nullable();
            $table->string('nilai', 50)->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->unsignedInteger('minggu_ke');
            $table->date('berlaku_dari')->nullable();
            $table->date('berlaku_sampai')->nullable();
            $table->integer('stok')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status_aktif');
            $table->index('minggu_ke');
        });

        Schema::create('user_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('minggu_ke');
            $table->timestamp('claimed_at')->useCurrent();

            $table->unique(['user_id', 'reward_id', 'minggu_ke'], 'uq_user_reward_minggu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_rewards');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('daily_logins');
    }
};
