<?php

namespace App\Models;

use App\Enums\BetStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'public_code', 'user_id', 'wallet_id', 'season_id',
        'match_id', 'market_id', 'outcome_id', 'stake',
        'profit_rate_snapshot', 'line_snapshot', 'label_snapshot',
        'display_odds_snapshot', 'close_at_snapshot',
        'market_type_snapshot', 'period_type_snapshot', 'selection_side_snapshot',
        'status', 'gross_payout', 'net_result', 'placed_at', 'settled_at', 'voided_at', 'metadata',
    ];

    protected $casts = [
        'stake' => 'integer',
        'gross_payout' => 'integer',
        'net_result' => 'integer',
        'profit_rate_snapshot' => 'decimal:4',
        'close_at_snapshot' => 'datetime',
        'placed_at' => 'datetime',
        'settled_at' => 'datetime',
        'voided_at' => 'datetime',
        'metadata' => 'array',
        'status' => BetStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(FootballMatch::class, 'match_id');
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function outcome(): BelongsTo
    {
        return $this->belongsTo(MarketOutcome::class, 'outcome_id');
    }

    public function getLabelSnapshotAttribute($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        $lower = strtolower($value);
        if (str_starts_with($lower, 'over ')) {
            return preg_replace('/^over /i', 'Tài ', $value);
        }
        if (str_starts_with($lower, 'under ')) {
            return preg_replace('/^under /i', 'Xỉu ', $value);
        }
        if ($lower === 'over') {
            return 'Tài';
        }
        if ($lower === 'under') {
            return 'Xỉu';
        }

        return $value;
    }

    public function getDisplayOddsSnapshotAttribute($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        $lower = strtolower($value);
        if (str_starts_with($lower, 'over ')) {
            return preg_replace('/^over /i', 'Tài ', $value);
        }
        if (str_starts_with($lower, 'under ')) {
            return preg_replace('/^under /i', 'Xỉu ', $value);
        }
        if (str_starts_with($lower, 'over')) {
            return preg_replace('/^over/i', 'Tài', $value);
        }
        if (str_starts_with($lower, 'under')) {
            return preg_replace('/^under/i', 'Xỉu', $value);
        }

        return $value;
    }
}
