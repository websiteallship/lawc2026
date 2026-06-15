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
        Schema::create('settlement_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('settlements')->cascadeOnDelete();
            $table->json('old_results');
            $table->json('new_results');
            $table->string('reason');
            $table->string('status')->default('PENDING'); // PENDING, EXECUTED
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_corrections');
    }
};
