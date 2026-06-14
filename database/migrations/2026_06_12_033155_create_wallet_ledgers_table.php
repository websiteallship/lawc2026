<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->integer('amount_available');
            $table->integer('amount_locked');
            $table->integer('balance_available_after');
            $table->integer('balance_locked_after');
            $table->unsignedBigInteger('bet_id')->nullable()->index();
            $table->unsignedBigInteger('settlement_id')->nullable()->index();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['wallet_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_ledgers');
    }
};
