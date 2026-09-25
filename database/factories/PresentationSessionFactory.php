<?php

namespace Database\Factories;

use App\Models\Presentation;
use App\Models\PresentationSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PresentationSession>
 */
class PresentationSessionFactory extends Factory
{
    protected $model = PresentationSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'presentation_id' => Presentation::factory(),
            'team_id' => fn (array $attributes) => Presentation::findOrFail((int) $attributes['presentation_id'])->team_id,
            'started_at' => Carbon::now()->subMinutes(30),
            'ended_at' => null,
            'last_seen_at' => Carbon::now()->subMinutes(30),
            'reaction_counts' => [],
            'reaction_total' => 0,
            'viewers' => [],
            'viewer_count' => 0,
        ];
    }

    public function ended(): static
    {
        return $this->state(fn () => [
            'ended_at' => Carbon::now()->subMinutes(5),
            'last_seen_at' => Carbon::now()->subMinutes(5),
        ]);
    }

    /**
     * @param  array<string, int>  $counts
     */
    public function withReactions(array $counts): static
    {
        return $this->state(fn () => [
            'reaction_counts' => $counts,
            'reaction_total' => array_sum($counts),
        ]);
    }
}
