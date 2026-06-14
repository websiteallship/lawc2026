<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->string('match_code', 50);
            $table->string('stage', 100);
            $table->string('home_team');
            $table->string('away_team');
            $table->timestamp('kickoff_at')->index();
            $table->string('timezone', 100)->default('Asia/Ho_Chi_Minh');
            $table->string('venue')->nullable();
            $table->string('status', 30)->default('SCHEDULED')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['season_id', 'match_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
