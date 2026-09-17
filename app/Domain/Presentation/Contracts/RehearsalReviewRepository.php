<?php

declare(strict_types=1);

namespace App\Domain\Presentation\Contracts;

use App\Domain\Presentation\Entities\RehearsalReviewEntity;

interface RehearsalReviewRepository
{
    /** Loads the review aggregate including its comments. */
    public function findById(int $id): RehearsalReviewEntity;

    /** Persists the review and any comments that do not have an id yet. */
    public function save(RehearsalReviewEntity $review): RehearsalReviewEntity;

    public function existsForRunAndReviewer(int $practiceRunId, int $reviewerUserId): bool;
}
