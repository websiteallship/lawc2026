<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    protected $fillable = [
        'level',
        'code',
        'name',
        'description',
        'target_value',
        'icon',
        'color',
        'is_repeatable',
        'cooldown_period',
    ];

    protected $casts = [
        'is_repeatable' => 'boolean',
        'level' => 'integer',
        'target_value' => 'integer',
    ];

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }
}
