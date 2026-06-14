<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('api.football_data_api_token', '');
        $this->migrator->add('api.is_auto_sync_enabled', false);
        $this->migrator->add('api.auto_sync_interval_minutes', 3);
        $this->migrator->add('api.competition_id', '2000');
    }
};
