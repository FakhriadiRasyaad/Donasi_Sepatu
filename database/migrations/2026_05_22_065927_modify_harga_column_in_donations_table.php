<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            if (Schema::hasColumn('donations', 'harga')) {
                // Ubah tipe kolom menjadi unsignedBigInteger agar cukup besar
                $table->unsignedBigInteger('harga')->nullable()->default(0)->change();
            } else {
                // Buat kolom baru jika belum ada
                $table->unsignedBigInteger('harga')->nullable()->default(0)->after('kondisi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            // Rollback: kembalikan ke integer biasa
            if (Schema::hasColumn('donations', 'harga')) {
                $table->unsignedInteger('harga')->nullable()->default(0)->change();
            }
        });
    }
};
