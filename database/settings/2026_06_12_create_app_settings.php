<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('app.app_name', 'World Cup 2026');
        $this->migrator->add('app.app_logo_url', null);
        $this->migrator->add('app.min_stake', 10);
        $this->migrator->add('app.max_stake_per_bet', 200);
        $this->migrator->add('app.max_stake_per_match', 500);
        $this->migrator->add('app.default_starting_leaves', 1000);
        $this->migrator->add('app.enable_local_logins', false);
    }
};
