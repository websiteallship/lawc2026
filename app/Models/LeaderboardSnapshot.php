<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardSnapshot extends Model
{
    public $timestamps = false; // Table không có updated_at

    protected $fillable = [
        'season_id', 'user_id', 'rank',
        'available_balance', 'locked_balance', 'total_balance',
        'total_staked', 'total_payout', 'net_profit',
        'total_bets', 'won_bets', 'lost_bets', 'push_bets',
        'exact_score_wins', 'roi', 'win_rate', 'snapshot_at',
    ];

    protected $casts = [
        'snapshot_at' => 'datetime',
        'roi' => 'decimal:4',
        'win_rate' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
