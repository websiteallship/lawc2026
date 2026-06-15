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
        Schema::table('achievements', function (Blueprint $table) {
            if (!Schema::hasColumn('achievements', 'code')) {
                $table->string('code')->unique()->after('id');
                $table->string('name')->after('code');
                $table->text('description')->nullable()->after('name');
                $table->string('icon')->nullable()->after('description');
                $table->string('color')->nullable()->after('icon');
                $table->boolean('is_repeatable')->default(false)->after('color');
                $table->string('cooldown_period')->nullable()->after('is_repeatable');
            }
        });

        Schema::table('user_achievements', function (Blueprint $table) {
            if (!Schema::hasColumn('user_achievements', 'user_id')) {
                $table->foreignId('user_id')->constrained()->cascadeOnDelete()->after('id');
                $table->foreignId('achievement_id')->constrained()->cascadeOnDelete()->after('user_id');
                $table->timestamp('awarded_at')->nullable()->after('achievement_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropColumn(['code', 'name', 'description', 'icon', 'color', 'is_repeatable', 'cooldown_period']);
        });

        Schema::table('user_achievements', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['achievement_id']);
            $table->dropColumn(['user_id', 'achievement_id', 'awarded_at']);
        });
    }
};
