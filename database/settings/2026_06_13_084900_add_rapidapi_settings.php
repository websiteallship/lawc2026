<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('api.rapidapi_key', '');
        $this->migrator->add('api.rapidapi_host', 'v3.football.api-sports.io');
        $this->migrator->add('api.bookmaker_id', 8);
    }
};
