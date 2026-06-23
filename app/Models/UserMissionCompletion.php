<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMissionCompletion extends Model
{
    protected $fillable = [
        'user_id',
        'mission_id',
        'mission_code',
        'mission_type',
        'week_key',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    /**
     * Tạo week_key theo ISO week: "2026-W25"
     */
    public static function weekKey(\DateTimeInterface|null $date = null): string
    {
        $dt = $date ? \Carbon\Carbon::instance($date) : now('Asia/Ho_Chi_Minh');
        return $dt->timezone('Asia/Ho_Chi_Minh')->format('o-\WW');
    }
}
