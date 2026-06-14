<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('api.live_score_provider', 'rapidapi_fallback_footballdata');
    }

    public function down(): void
    {
        $this->migrator->delete('api.live_score_provider');
    }
};
