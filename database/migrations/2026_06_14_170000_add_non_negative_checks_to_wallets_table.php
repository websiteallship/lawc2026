<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE wallets ADD CONSTRAINT check_available_balance_non_negative CHECK (available_balance >= 0)');
            DB::statement('ALTER TABLE wallets ADD CONSTRAINT check_locked_balance_non_negative CHECK (locked_balance >= 0)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE wallets DROP CONSTRAINT IF EXISTS check_available_balance_non_negative');
            DB::statement('ALTER TABLE wallets DROP CONSTRAINT IF EXISTS check_locked_balance_non_negative');
        }
    }
};
