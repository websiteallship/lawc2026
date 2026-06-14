<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'status', 'default_starting_leaves',
        'starts_at', 'ends_at', 'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'default_starting_leaves' => 'integer',
    ];

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }
}
