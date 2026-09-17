<?php

declare(strict_types=1);

namespace App\Application\Actions\Presentations;

use App\Application\Commands\AddReviewCommentCommand;
use App\Domain\Presentation\Contracts\RehearsalReviewRepository;
use App\Domain\Presentation\Entities\RehearsalReviewEntity;
use App\Domain\Presentation\Events\ReviewCommentAdded;

class AddReviewComment
{
    public function __construct(
        private readonly RehearsalReviewRepository $reviews,
    ) {}

    public function execute(AddReviewCommentCommand $command): RehearsalReviewEntity
    {
        $review = $this->reviews->findById($command->rehearsalReviewId);

        $review->addComment($command->slideNumber, $command->message);

        $review = $this->reviews->save($review);

        ReviewCommentAdded::dispatch($review->id);

        return $review;
    }
}
