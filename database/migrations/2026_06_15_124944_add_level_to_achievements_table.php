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
            $table->unsignedTinyInteger('level')->nullable()->after('id')->comment('Cấp bậc của thành tựu (1-9)');
            $table->unsignedInteger('target_value')->nullable()->after('is_repeatable')->comment('Giá trị mục tiêu cần đạt được');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropColumn(['level', 'target_value']);
        });
    }
};
