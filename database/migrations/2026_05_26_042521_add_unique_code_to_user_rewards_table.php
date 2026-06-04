<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_rewards', function (Blueprint $table) {
            $table->string('unique_code', 50)->nullable()->unique()->after('minggu_ke');
        });
    }

    public function down(): void
    {
        Schema::table('user_rewards', function (Blueprint $table) {
            $table->dropColumn('unique_code');
        });
    }
};
