<?php

namespace Database\Factories;

use App\Domain\Presentation\Entities\RehearsalReviewEntity;
use App\Models\RehearsalModel;
use App\Models\RehearsalReviewModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RehearsalReviewModel>
 */
class RehearsalReviewModelFactory extends Factory
{
    protected $model = RehearsalReviewModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'practice_run_id' => RehearsalModel::factory(),
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
