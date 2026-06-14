<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ApiSettings extends Settings
{
    public string $football_data_api_token;

    public string $rapidapi_key;

    public string $rapidapi_host;

    public int $bookmaker_id;

    public bool $is_auto_sync_enabled;

    public int $auto_sync_interval_minutes;

    public string $competition_id;

    public bool $is_rapidapi_auto_sync_enabled;

    public int $rapidapi_auto_sync_interval_minutes;

    public string $live_score_provider;

    public static function group(): string
    {
        return 'api';
    }
}
