<?php

namespace Database\Factories;

use App\Models\Bet;
use App\Models\Season;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BetFactory extends Factory
{
    protected $model = Bet::class;

    public function definition(): array
    {
        $user = User::factory()->create();
        $season = Season::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'season_id' => $season->id]);

        return [
            'public_code' => 'BET-'.strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'season_id' => $season->id,
            'match_id' => 1,
            'market_id' => 1,
            'outcome_id' => 1,
            'stake' => 100,
            'profit_rate_snapshot' => '0.9000',
            'line_snapshot' => null,
            'label_snapshot' => 'Home -0.5',
            'display_odds_snapshot' => 'Home -0.5 ăn 0.90',
            'close_at_snapshot' => now()->addHour(),
            'market_type_snapshot' => 'ASIAN_HANDICAP',
            'period_type_snapshot' => 'FULL_TIME',
            'selection_side_snapshot' => 'HOME',
            'status' => 'PENDING',
            'placed_at' => now(),
        ];
    }
}
