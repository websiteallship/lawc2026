<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettlementCorrection extends Model
{
    protected $fillable = [
        'settlement_id',
        'old_results',
        'new_results',
        'reason',
        'status',
        'created_by',
        'executed_by',
        'executed_at',
    ];

    protected $casts = [
        'old_results' => 'array',
        'new_results' => 'array',
        'executed_at' => 'datetime',
    ];

    public function settlement()
    {
        return $this->belongsTo(Settlement::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function executor()
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
