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
        Schema::table('matches', function (Blueprint $table) {
            $table->string('api_id', 50)->nullable()->index()->after('id')->comment('Football-data.org match ID');
            $table->integer('home_score')->nullable()->after('away_team');
            $table->integer('away_score')->nullable()->after('home_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['api_id', 'home_score', 'away_score']);
        });
    }
};
