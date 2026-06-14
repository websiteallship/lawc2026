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
        Schema::create('match_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->integer('minute');
            $table->integer('injury_time')->nullable();
            $table->string('type'); // GOAL, CARD, SUB
            $table->string('team_type'); // HOME, AWAY
            $table->string('player_name')->nullable();
            $table->string('related_player_name')->nullable(); // assist or playerOut
            $table->string('detail')->nullable(); // YELLOW, RED, REGULAR, OWN_GOAL, PENALTY
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_events');
    }
};
