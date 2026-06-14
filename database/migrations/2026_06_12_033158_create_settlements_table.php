<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->string('period_type', 30);
            $table->string('status', 30);
            $table->integer('result_home_score')->nullable();
            $table->integer('result_away_score')->nullable();
            $table->integer('total_bets')->default(0);
            $table->integer('total_stake')->default(0);
            $table->integer('total_payout')->default(0);
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('executed_at')->nullable();
            $table->text('reason')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
