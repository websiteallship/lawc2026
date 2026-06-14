<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->string('public_code', 50)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outcome_id')->constrained('market_outcomes')->cascadeOnDelete();
            $table->integer('stake');
            $table->decimal('profit_rate_snapshot', 10, 4);
            $table->decimal('line_snapshot', 6, 2)->nullable();
            $table->string('label_snapshot');
            $table->string('display_odds_snapshot');
            $table->timestamp('close_at_snapshot');
            $table->string('market_type_snapshot', 50);
            $table->string('period_type_snapshot', 30);
            $table->string('selection_side_snapshot', 30)->nullable();
            $table->string('status', 30)->default('PENDING');
            $table->integer('gross_payout')->nullable();
            $table->integer('net_result')->nullable();
            $table->timestamp('placed_at');
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['market_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
