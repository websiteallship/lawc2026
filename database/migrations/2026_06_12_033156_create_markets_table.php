<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->string('period_type', 30);
            $table->string('market_type', 50);
            $table->string('name');
            $table->timestamp('open_at');
            $table->timestamp('close_at');
            $table->string('status', 30)->default('DRAFT');
            $table->integer('display_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'close_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markets');
    }
};
