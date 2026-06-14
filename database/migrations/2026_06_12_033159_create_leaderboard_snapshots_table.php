<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboard_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('rank');
            $table->integer('available_balance');
            $table->integer('locked_balance');
            $table->integer('total_balance');
            $table->integer('total_staked');
            $table->integer('total_payout');
            $table->integer('net_profit');
            $table->integer('total_bets');
            $table->integer('won_bets');
            $table->integer('lost_bets');
            $table->integer('push_bets');
            $table->integer('exact_score_wins');
            $table->decimal('roi', 10, 4)->nullable();
            $table->decimal('win_rate', 10, 4)->nullable();
            $table->timestamp('snapshot_at');

            $table->index(['season_id', 'snapshot_at']);
            $table->index('rank');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_snapshots');
    }
};
