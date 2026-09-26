<?php

namespace Database\Factories;

use App\Domain\Presentation\Entities\RehearsalReviewEntity;
use App\Models\Rehearsal;
use App\Models\RehearsalReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RehearsalReview>
 */
class RehearsalReviewFactory extends Factory
{
    protected $model = RehearsalReview::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'practice_run_id' => Rehearsal::factory(),
            'requester_user_id' => User::factory(),
            'reviewer_user_id' => User::factory(),
            'status' => RehearsalReviewEntity::STATUS_PENDING,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => RehearsalReviewEntity::STATUS_COMPLETED,
        ]);
    }
}
