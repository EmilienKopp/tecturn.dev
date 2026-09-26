<?php

namespace Database\Factories;

use App\Domain\Presentation\ValueObjects\PresentationContent;
use App\Models\Presentation;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Presentation>
 */
class PresentationFactory extends Factory
{
    protected $model = Presentation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->sentence(3),
            'is_private' => false,
            'content' => PresentationContent::empty()->toArray(),
        ];
    }

    public function withSlides(int $count): static
    {
        return $this->state(function () use ($count) {
            $slides = [];

            for ($i = 1; $i <= $count; $i++) {
                $slides[] = [
                    'id' => "slide-{$i}",
                    'layout' => 'free',
                    'background' => null,
                    'slots' => [
                        'main' => [
                            [
                                'id' => "block-{$i}-1",
                                'type' => 'text',
                                'content' => fake()->sentence(),
                                'style' => [
                                    'fontSize' => '2rem',
                                    'x' => '10',
                                    'y' => '20',
                                    'width' => '30',
                                ],
                                'transition' => null,
                            ],
                        ],
                    ],
                ];
            }

            return ['content' => ['version' => '1.0', 'slides' => $slides]];
        });
    }

    /** An AI draft still building (requested, not yet completed). */
    public function generatingDraft(string $plan = '# My plan'): static
    {
        return $this->state(fn () => [
            'name' => 'Generating deck…',
            'draft_plan' => $plan,
            'draft_requested_at' => now(),
            'draft_completed_at' => null,
            'draft_failed_at' => null,
            'draft_error' => null,
        ]);
    }

    /** An AI draft whose build failed. */
    public function failedDraft(string $error = 'provider exploded', string $plan = '# My plan'): static
    {
        return $this->state(fn () => [
            'draft_plan' => $plan,
            'draft_requested_at' => now(),
            'draft_completed_at' => null,
            'draft_failed_at' => now(),
            'draft_error' => $error,
        ]);
    }

    /** A Google Slides external deck. */
    public function googleSlides(string $url = 'https://docs.google.com/presentation/d/e/abc/pub'): static
    {
        return $this->state(fn () => [
            'source' => ['type' => 'google_slides', 'externalUrl' => $url],
        ]);
    }

    /**
     * A PDF external deck. The PDF media is attached separately in the test,
     * since a factory can't fabricate a real uploaded file.
     */
    public function pdf(): static
    {
        return $this->state(fn () => [
            'source' => ['type' => 'pdf', 'externalUrl' => null],
        ]);
    }
}
