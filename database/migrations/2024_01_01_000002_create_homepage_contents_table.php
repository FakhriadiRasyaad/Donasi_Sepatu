<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_contents', function (Blueprint $table) {
            $table->id();
            $table->string('key_name', 100)->unique();
            $table->string('label', 150);
            $table->enum('type', ['text', 'image', 'json'])->default('text');
            $table->longText('value_text')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_contents');
    }
};
