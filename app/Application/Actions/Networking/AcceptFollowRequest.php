<?php

declare(strict_types=1);

namespace App\Application\Actions\Networking;

use App\Application\Commands\RespondToFollowRequestCommand;
use App\Domain\Networking\Contracts\UserFollowRepository;
use App\Domain\Networking\Exceptions\FollowRequestNotFound;

class AcceptFollowRequest
{
    public function __construct(
        private readonly UserFollowRepository $userFollows,
    ) {}

    public function execute(RespondToFollowRequestCommand $command): void
    {
        if (! $this->userFollows->hasPendingRequest($command->followerUserId, $command->followedUserId)) {
            throw new FollowRequestNotFound('There is no pending follow request from this person.');
        }

        $this->userFollows->accept($command->followerUserId, $command->followedUserId);
    }
}
