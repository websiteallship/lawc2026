<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('selection_side', 30)->nullable();
            $table->integer('score_home')->nullable();
            $table->integer('score_away')->nullable();
            $table->decimal('line_value', 6, 2)->nullable();
            $table->decimal('profit_rate', 10, 4);
            $table->decimal('decimal_odds', 10, 4)->nullable();
            $table->string('status', 30)->default('ACTIVE');
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_outcomes');
    }
};
