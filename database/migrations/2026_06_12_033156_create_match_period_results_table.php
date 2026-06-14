<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_period_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->string('period_type', 30);
            $table->integer('home_score');
            $table->integer('away_score');
            $table->string('status', 30)->default('DRAFT');
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('source_note')->nullable();
            $table->timestamps();

            $table->unique(['match_id', 'period_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_period_results');
    }
};
