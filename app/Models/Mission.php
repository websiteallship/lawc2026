<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mission extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function rewardAchievement()
    {
        return $this->belongsTo(Achievement::class, 'reward_achievement_id');
    }

    public function userMissions()
    {
        return $this->hasMany(UserMission::class);
    }
}
