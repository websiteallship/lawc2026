<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchEvent extends Model
{
    protected $fillable = [
        'match_id',
        'minute',
        'injury_time',
        'type', // GOAL, CARD, SUB
        'team_type', // HOME, AWAY
        'player_name',
        'related_player_name',
        'detail', // YELLOW, RED, REGULAR, OWN_GOAL, PENALTY
    ];

    public function match()
    {
        return $this->belongsTo(FootballMatch::class, 'match_id');
    }
}
