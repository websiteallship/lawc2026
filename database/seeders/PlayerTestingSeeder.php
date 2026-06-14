<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlayerTestingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $user = User::firstOrCreate(
                ['email' => "player{$i}@test.com"],
                [
                    'name' => "Test Player {$i}",
                    'password' => Hash::make('password'),
                ]
            );

            // Assign role
            if (! $user->hasRole('player')) {
                $user->assignRole('player');
            }
        }
    }
}
