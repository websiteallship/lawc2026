<?php

namespace Database\Factories;

use App\Models\Season;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'season_id' => Season::factory(),
            'available_balance' => 0,
            'locked_balance' => 0,
            'total_staked' => 0,
            'total_payout' => 0,
            'net_profit' => 0,
            'status' => 'ACTIVE',
        ];
    }
}
