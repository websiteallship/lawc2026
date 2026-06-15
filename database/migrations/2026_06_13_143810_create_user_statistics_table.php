<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->integer('total_bets')->default(0);
            $table->integer('settled_bets')->default(0);
            $table->integer('won_bets')->default(0);
            $table->integer('lost_bets')->default(0);
            $table->integer('push_bets')->default(0);
            $table->integer('voided_bets')->default(0);
            
            $table->bigInteger('total_staked')->default(0);
            $table->bigInteger('total_payout')->default(0);
            $table->bigInteger('net_profit')->default(0);
            
            $table->decimal('roi', 10, 2)->default(0);
            $table->decimal('win_rate', 5, 2)->default(0);
            
            $table->integer('exact_score_wins')->default(0);
            $table->integer('current_win_streak')->default(0);
            $table->integer('longest_win_streak')->default(0);

            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_statistics');
    }
};
