<?php

declare(strict_types=1);

namespace App\Application\Actions\Presentations;

use App\Application\Commands\RequestRehearsalReviewCommand;
use App\Domain\Networking\Contracts\UserFollowRepository;
use App\Domain\Presentation\Contracts\RehearsalReviewRepository;
use App\Domain\Presentation\Entities\RehearsalReviewEntity;
use App\Domain\Presentation\Events\RehearsalReviewRequested;
use App\Domain\Presentation\Exceptions\DuplicateReviewRequest;
use App\Domain\Presentation\Exceptions\ReviewerDoesNotFollowRequester;

class RequestRehearsalReview
{
    public function __construct(
        private readonly RehearsalReviewRepository $reviews,
        private readonly UserFollowRepository $userFollows,
    ) {}

    /**
     * Asks a follower to review a rehearsal run. Only people who follow the
     * requester can be asked, and each person only once per run.
     */
    public function execute(RequestRehearsalReviewCommand $command): RehearsalReviewEntity
    {
        if (! $this->userFollows->isFollowing($command->reviewerUserId, $command->requesterUserId)) {
            throw new ReviewerDoesNotFollowRequester('Reviews can only be requested from people who follow you.');
        }

        if ($this->reviews->existsForRunAndReviewer($command->rehearsalId, $command->reviewerUserId)) {
            throw new DuplicateReviewRequest('This person has already been asked to review this rehearsal.');
        }

        $review = $this->reviews->save(new RehearsalReviewEntity(
            practice_run_id: $command->rehearsalId,
            requester_user_id: $command->requesterUserId,
            reviewer_user_id: $command->reviewerUserId,
        ));

        RehearsalReviewRequested::dispatch($review->id);

        return $review;
    }
}
