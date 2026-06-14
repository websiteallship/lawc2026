<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Market extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id', 'period_type', 'market_type', 'name',
        'open_at', 'close_at', 'status', 'display_order', 'created_by',
        'locked_at', 'settled_at', 'voided_at', 'void_reason',
    ];

    protected $casts = [
        'open_at' => 'datetime',
        'close_at' => 'datetime',
        'locked_at' => 'datetime',
        'settled_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(FootballMatch::class, 'match_id');
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(MarketOutcome::class);
    }

    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }
}
