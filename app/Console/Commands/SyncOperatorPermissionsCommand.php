<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SyncOperatorPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shield:sync-operator';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync permissions for operator role in production';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $role = Role::firstOrCreate(['name' => 'operator']);

        // Operator permissions from matrix (adapted to Shield's PascalCase convention)
        $permissions = [
            // Matches
            'ViewAny:FootballMatch',
            'View:FootballMatch',
            'Create:FootballMatch',
            'Update:FootballMatch',
            
            // Markets
            'ViewAny:Market',
            'View:Market',
            'Create:Market',
            'Update:Market',
            
            // Users
            'ViewAny:User',
            'View:User',
            'Create:User',
            'Update:User',
            'Delete:User',
            
            // Bets
            'ViewAny:Bet',
            'View:Bet',
            
            // Seasons
            'ViewAny:Season',
            'View:Season',
            
            // Settlements
            'ViewAny:Settlement',
            'View:Settlement',

            // Wallets
            'ViewAny:Wallet',
            'View:Wallet',
            'ViewAny:WalletLedger',
            'View:WalletLedger',

            // Leaderboard
            'ViewAny:Leaderboard',

        ];

        // Ensure these permissions exist, in case Shield hasn't generated them
        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        $role->syncPermissions($permissions);

        $this->info('Operator permissions synced successfully!');
    }
}
