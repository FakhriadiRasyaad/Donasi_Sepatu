<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nama_sepatu', 150);
            $table->string('ukuran', 10);
            $table->unsignedTinyInteger('kondisi')->comment('Persentase kelayakan 0-100');
            $table->text('deskripsi')->nullable();
            $table->string('foto_path', 255);
            $table->enum('metode_pengiriman', ['antar_langsung', 'ekspedisi'])->default('ekspedisi');
            $table->string('nama_ekspedisi', 100)->nullable();
            $table->string('no_resi', 100)->nullable();
            $table->enum('status', ['pending', 'diterima', 'disalurkan', 'ditolak'])->default('pending');
            $table->text('catatan_admin')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
