<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'season_id',
        'available_balance',
        'locked_balance',
        'total_staked',
        'total_payout',
        'net_profit',
        'status',
    ];

    protected $casts = [
        'available_balance' => 'integer',
        'locked_balance' => 'integer',
        'total_staked' => 'integer',
        'total_payout' => 'integer',
        'net_profit' => 'integer',
    ];

    public function getTotalBalanceAttribute(): int
    {
        return $this->available_balance + $this->locked_balance;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(WalletLedger::class);
    }
}
