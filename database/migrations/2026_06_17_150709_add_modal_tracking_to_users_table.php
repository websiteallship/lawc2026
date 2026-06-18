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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_daily_briefing_at')->nullable()->after('last_login_at');
            $table->timestamp('last_daily_ranking_shown_at')->nullable()->after('last_daily_briefing_at');
            $table->timestamp('last_celebration_shown_at')->nullable()->after('last_daily_ranking_shown_at');
            $table->timestamp('last_settlement_summary_shown_at')->nullable()->after('last_celebration_shown_at');
            $table->timestamp('last_reengagement_shown_at')->nullable()->after('last_settlement_summary_shown_at');
            $table->timestamp('last_active_at')->nullable()->after('last_reengagement_shown_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_daily_briefing_at',
                'last_daily_ranking_shown_at',
                'last_celebration_shown_at',
                'last_settlement_summary_shown_at',
                'last_reengagement_shown_at',
                'last_active_at',
            ]);
        });
    }
};
