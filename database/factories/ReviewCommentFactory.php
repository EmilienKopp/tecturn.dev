<?php

namespace Database\Factories;

use App\Models\RehearsalReview;
use App\Models\ReviewComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewComment>
 */
class ReviewCommentFactory extends Factory
{
    protected $model = ReviewComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rehearsal_review_id' => RehearsalReview::factory(),
            'slide_number' => $this->faker->numberBetween(0, 5),
            'message' => $this->faker->sentence(),
        ];
    }
}
