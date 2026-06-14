<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementItem extends Model
{
    protected $fillable = [
        'settlement_id', 'bet_id', 'user_id',
        'stake', 'profit_rate_snapshot', 'decimal_odds_snapshot',
        'result_status', 'gross_payout', 'net_result', 'calculation_detail',
    ];

    protected $casts = [
        'calculation_detail' => 'array',
        'profit_rate_snapshot' => 'decimal:4',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
