<?php

namespace Database\Factories;

use App\Models\RehearsalReviewModel;
use App\Models\ReviewCommentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewCommentModel>
 */
class ReviewCommentModelFactory extends Factory
{
    protected $model = ReviewCommentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rehearsal_review_id' => RehearsalReviewModel::factory(),
            'slide_number' => $this->faker->numberBetween(0, 5),
            'message' => $this->faker->sentence(),
        ];
    }
}
