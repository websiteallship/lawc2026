<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_achievements', function (Blueprint $table) {
            $table->unique(['user_id', 'achievement_id'], 'user_achievement_unique');
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('bets', function (Blueprint $table) {
                $table->index(['user_id', 'status', 'market_type_snapshot'], 'bets_user_status_market_index');
            });
            Schema::table('markets', function (Blueprint $table) {
                $table->index(['status', 'close_at'], 'markets_status_close_at_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->dropIndex('markets_status_close_at_index');
        });

        Schema::table('bets', function (Blueprint $table) {
            $table->dropIndex('bets_user_status_market_index');
        });

        Schema::table('user_achievements', function (Blueprint $table) {
            $table->dropUnique('user_achievement_unique');
        });
    }
};
