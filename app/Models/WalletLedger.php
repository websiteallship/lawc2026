<?php

namespace App\Models;

use App\Enums\LedgerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletLedger extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'wallet_id',
        'user_id',
        'season_id',
        'type',
        'amount_available',
        'amount_locked',
        'balance_available_after',
        'balance_locked_after',
        'bet_id',
        'settlement_id',
        'actor_id',
        'reason',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'type' => LedgerType::class,
        'amount_available' => 'integer',
        'amount_locked' => 'integer',
        'balance_available_after' => 'integer',
        'balance_locked_after' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class);
    }
}
