<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('api.is_rapidapi_auto_sync_enabled', false);
        $this->migrator->add('api.rapidapi_auto_sync_interval_minutes', 15);
    }
};
