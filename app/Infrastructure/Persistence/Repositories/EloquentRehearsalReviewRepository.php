<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Presentation\Contracts\RehearsalReviewRepository;
use App\Domain\Presentation\Entities\RehearsalReviewEntity;
use App\Models\RehearsalReviewModel;

class EloquentRehearsalReviewRepository implements RehearsalReviewRepository
{
    public function findById(int $id): RehearsalReviewEntity
    {
        return RehearsalReviewModel::with('comments')->findOrFail($id)->toEntity();
    }

    public function save(RehearsalReviewEntity $review): RehearsalReviewEntity
    {
        $attributes = [
            'practice_run_id' => $review->practice_run_id,
            'requester_user_id' => $review->requester_user_id,
            'reviewer_user_id' => $review->reviewer_user_id,
            'status' => $review->status,
        ];

        if ($review->id === null) {
            $model = RehearsalReviewModel::create($attributes);
        } else {
            $model = RehearsalReviewModel::findOrFail($review->id);
            $model->update($attributes);
        }

        foreach ($review->comments as $comment) {
            if ($comment->id === null) {
                $model->comments()->create([
                    'slide_number' => $comment->slide_number,
                    'message' => $comment->message,
                ]);
            }
        }

        return $model->refresh()->load('comments')->toEntity();
    }

    public function existsForRunAndReviewer(int $practiceRunId, int $reviewerUserId): bool
    {
        return RehearsalReviewModel::query()
            ->where('practice_run_id', $practiceRunId)
            ->where('reviewer_user_id', $reviewerUserId)
            ->exists();
    }
}
