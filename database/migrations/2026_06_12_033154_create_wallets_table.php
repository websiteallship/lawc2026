<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->integer('available_balance')->default(0);
            $table->integer('locked_balance')->default(0);
            $table->integer('total_staked')->default(0);
            $table->integer('total_payout')->default(0);
            $table->integer('net_profit')->default(0);
            $table->string('status', 30)->default('ACTIVE');
            $table->timestamps();

            $table->unique(['user_id', 'season_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
