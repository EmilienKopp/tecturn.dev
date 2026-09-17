<?php

declare(strict_types=1);

namespace App\Application\Actions\Presentations;

use App\Application\Commands\CompleteRehearsalReviewCommand;
use App\Domain\Presentation\Contracts\RehearsalReviewRepository;
use App\Domain\Presentation\Entities\RehearsalReviewEntity;
use App\Domain\Presentation\Events\RehearsalReviewCompleted;

class CompleteRehearsalReview
{
    public function __construct(
        private readonly RehearsalReviewRepository $reviews,
    ) {}

    public function execute(CompleteRehearsalReviewCommand $command): RehearsalReviewEntity
    {
        $review = $this->reviews->findById($command->rehearsalReviewId);

        $review->complete();

        $review = $this->reviews->save($review);

        RehearsalReviewCompleted::dispatch($review->id);

        return $review;
    }
}
