<?php

namespace Database\Factories;

use App\Domain\Presentation\ValueObjects\PresentationContent;
use App\Models\PracticeRunModel;
use App\Models\PresentationModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PracticeRunModel>
 */
class PracticeRunModelFactory extends Factory
{
    protected $model = PracticeRunModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'presentation_id' => PresentationModel::factory(),
            'team_id' => fn (array $attributes) => PresentationModel::findOrFail($attributes['presentation_id'])->team_id,
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
}
