<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FootballMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'api_id', 'season_id', 'match_code', 'stage', 'group', 'api_round', 'bracket_position',
        'home_team', 'away_team', 'home_score', 'away_score',
        'kickoff_at', 'finished_at', 'timezone', 'venue', 'status', 'created_by',
        'odds_fetch_status',
    ];

    protected $casts = [
        'kickoff_at' => 'datetime',
        'finished_at' => 'datetime',
        'odds_fetch_status' => 'array',
        'bracket_position' => 'integer',
    ];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function events()
    {
        return $this->hasMany(MatchEvent::class, 'match_id');
    }

    public function markets(): HasMany
    {
        return $this->hasMany(Market::class, 'match_id');
    }

    public function periodResults(): HasMany
    {
        return $this->hasMany(MatchPeriodResult::class, 'match_id');
    }
}
