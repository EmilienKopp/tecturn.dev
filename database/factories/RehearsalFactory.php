<?php

namespace Database\Factories;

use App\Domain\Presentation\ValueObjects\PresentationContent;
use App\Models\Presentation;
use App\Models\Rehearsal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Rehearsal>
 */
class RehearsalFactory extends Factory
{
    protected $model = Rehearsal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'presentation_id' => Presentation::factory(),
            'team_id' => fn (array $attributes) => Presentation::findOrFail($attributes['presentation_id'])->team_id,
            'started_at' => Carbon::now()->subMinutes(20),
            'ended_at' => Carbon::now()->subMinutes(5),
            'duration_seconds' => 900,
            'slide_timings' => [],
            'content' => PresentationContent::empty()->toArray(),
            'flow' => null,
        ];
    }

    /**
     * @param  list<array{slide: int, seconds: int}>  $timings
     */
    public function withSlideTimings(array $timings): static
    {
        return $this->state(fn () => [
            'slide_timings' => $timings,
            'duration_seconds' => array_sum(array_column($timings, 'seconds')),
        ]);
    }

    /**
     * @param  list<array{at_ms: int, slide: int, step: int}>|null  $events
     */
    public function withStepEvents(?array $events = null): static
    {
        return $this->state(fn () => [
            'step_events' => $events ?? [
                ['at_ms' => 0, 'slide' => 0, 'step' => 0],
                ['at_ms' => 4000, 'slide' => 0, 'step' => 1],
                ['at_ms' => 9000, 'slide' => 1, 'step' => 0],
            ],
        ]);
    }
}
