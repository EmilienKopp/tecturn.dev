<?php

namespace Database\Factories;

use App\Models\Talk;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Talk>
 */
class TalkFactory extends Factory
{
    protected $model = Talk::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'title' => fake()->sentence(3),
        ];
    }
}
