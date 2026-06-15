<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStatistic extends Model
{
    protected $fillable = [
        'user_id',
        'total_bets',
        'settled_bets',
        'won_bets',
        'lost_bets',
        'push_bets',
        'voided_bets',
        'total_staked',
        'total_payout',
        'net_profit',
        'roi',
        'win_rate',
        'exact_score_wins',
        'current_win_streak',
        'longest_win_streak',
    ];

    protected $casts = [
        'roi' => 'decimal:2',
        'win_rate' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
