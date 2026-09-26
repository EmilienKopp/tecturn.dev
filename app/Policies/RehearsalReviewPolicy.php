<?php

namespace App\Policies;

use App\Domain\Presentation\Entities\RehearsalReviewEntity;
use App\Models\RehearsalReview;
use App\Models\User;

class RehearsalReviewPolicy
{
    /**
     * Determine whether the user can view the review page.
     */
    public function view(User $user, RehearsalReview $review): bool
    {
        return $user->id === $review->reviewer_user_id;
    }

    /**
     * Determine whether the user can view the review as its requester.
     */
    public function viewReceived(User $user, RehearsalReview $review): bool
    {
        return $user->id === $review->requester_user_id;
    }

    /**
     * Determine whether the user can add comments to the review.
     */
    public function comment(User $user, RehearsalReview $review): bool
    {
        return $user->id === $review->reviewer_user_id
            && $review->status === RehearsalReviewEntity::STATUS_PENDING;
    }

    /**
     * Determine whether the user can mark the review as completed.
     */
    public function complete(User $user, RehearsalReview $review): bool
    {
        return $user->id === $review->reviewer_user_id
            && $review->status === RehearsalReviewEntity::STATUS_PENDING;
    }
}
