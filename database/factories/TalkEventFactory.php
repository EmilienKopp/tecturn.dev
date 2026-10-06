<?php

namespace Database\Factories;

use App\Models\TalkEvent;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<TalkEvent>
 */
class TalkEventFactory extends Factory
{
    protected $model = TalkEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'talk_id' => null,
            'name' => fake()->sentence(3),
            'date' => Carbon::now()->addWeek()->toDateString(),
            'start_time' => null,
        ];
    }

    public function withStartTime(string $time = '14:30'): static
    {
        return $this->state(fn () => ['start_time' => $time]);
    }
}
