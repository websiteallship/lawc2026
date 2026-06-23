<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_mission_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
            $table->string('mission_code', 100)->index();
            $table->string('mission_type', 20)->index();   // daily | weekly | permanent
            $table->string('week_key', 10)->nullable();    // e.g. "2026-W25" — null for daily/permanent
            $table->timestamp('completed_at');
            $table->timestamps();

            // Một user chỉ hoàn thành 1 mission 1 lần/tuần (weekly) hoặc 1 lần/ngày (daily)
            $table->unique(['user_id', 'mission_id', 'week_key'], 'umc_unique_per_period');
            $table->index(['user_id', 'mission_type']);
            $table->index(['user_id', 'week_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_mission_completions');
    }
};
