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
            // Raw round string from API (e.g. "Round of 16 - 3", "Quarter-finals - 2")
            $table->string('api_round', 100)->nullable()->after('group');
            // Numeric bracket position derived from api_round for sorting in bracket view
            $table->smallInteger('bracket_position')->nullable()->after('api_round');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['api_round', 'bracket_position']);
        });
    }
};
