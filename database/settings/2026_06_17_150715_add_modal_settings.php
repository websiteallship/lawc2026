<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('app.welcome_modal_enabled', true);
        $this->migrator->add('app.welcome_modal_cooldown_hours', 12);
        $this->migrator->add('app.match_reminder_window_hours', 5);
        $this->migrator->add('app.match_reminder_cooldown_hours', 4);
        $this->migrator->add('app.match_reminder_enabled', true);
        $this->migrator->add('app.reengagement_absent_days', 3);
    }
};
