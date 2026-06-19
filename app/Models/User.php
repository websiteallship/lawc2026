<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'is_test_user',
        'accepted_rules_at',
        'last_login_at',
        'last_daily_briefing_at',
        'last_daily_ranking_shown_at',
        'last_celebration_shown_at',
        'last_settlement_summary_shown_at',
        'last_reengagement_shown_at',
        'last_active_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'              => 'datetime',
            'password'                       => 'hashed',
            'is_test_user'                   => 'boolean',
            'last_daily_briefing_at'         => 'datetime',
            'last_daily_ranking_shown_at'    => 'datetime',
            'last_celebration_shown_at'      => 'datetime',
            'last_settlement_summary_shown_at' => 'datetime',
            'last_reengagement_shown_at'     => 'datetime',
            'last_active_at'                 => 'datetime',
        ];
    }

    /**
     * Scope loại bỏ test users (dùng cho admin dashboard reporting)
     */
    public function scopeRealUsers($query)
    {
        return $query->where('is_test_user', false);
    }

    /**
     * Get the wallets for the user.
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Get the wallet ledgers for the user.
     */
    public function walletLedgers(): HasMany
    {
        return $this->hasMany(WalletLedger::class);
    }

    /**
     * Determine if the user can access the given panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasAnyRole(['super_admin', 'operator', 'settlement_manager', 'auditor']);
        }

        if ($panel->getId() === 'player') {
            return $this->hasRole('player') || $this->hasRole('super_admin');
        }

        return false;
    }
}
