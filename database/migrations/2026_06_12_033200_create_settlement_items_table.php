<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('stake');
            $table->decimal('profit_rate_snapshot', 10, 4);
            $table->decimal('decimal_odds_snapshot', 10, 4)->nullable();
            $table->string('result_status', 30);
            $table->integer('gross_payout');
            $table->integer('net_result');
            $table->jsonb('calculation_detail')->nullable();
            $table->timestamps();

            $table->unique(['settlement_id', 'bet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_items');
    }
};
