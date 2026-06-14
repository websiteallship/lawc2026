<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MarketOutcome extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Update the parent market's timestamp.
     *
     * @var array
     */
    protected $touches = ['market'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'market_id', 'label', 'selection_side', 'score_home', 'score_away',
        'line_value', 'profit_rate', 'decimal_odds', 'status', 'display_order',
    ];

    protected $casts = [
        'score_home' => 'integer',
        'score_away' => 'integer',
        'profit_rate' => 'decimal:4',
        'decimal_odds' => 'decimal:4',
        'line_value' => 'decimal:2',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}
