<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AppSettings extends Settings
{
    public string $app_name;

    public ?string $app_logo_url;

    public int $min_stake;

    public int $max_stake_per_bet;

    public int $max_stake_per_match;

    public int $default_starting_leaves;

    public bool $enable_local_logins;

    public bool $welcome_modal_enabled;

    public int $welcome_modal_cooldown_hours;

    public int $match_reminder_window_hours;

    public int $match_reminder_cooldown_hours;

    public bool $match_reminder_enabled;

    public int $reengagement_absent_days;

    public static function group(): string
    {
        return 'app';
    }
}
