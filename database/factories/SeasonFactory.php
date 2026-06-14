<?php

namespace Database\Factories;

use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        return [
            'code' => 'S'.$this->faker->unique()->numerify('###'),
            'name' => 'Season '.$this->faker->year(),
            'status' => 'ACTIVE',
            'default_starting_leaves' => 1000,
            'starts_at' => now()->subDays(30),
            'ends_at' => now()->addDays(60),
        ];
    }
}
