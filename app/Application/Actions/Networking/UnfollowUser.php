<?php

declare(strict_types=1);

namespace App\Application\Actions\Networking;

use App\Application\Commands\UnfollowUserCommand;
use App\Domain\Networking\Contracts\UserFollowRepository;
use InvalidArgumentException;

class UnfollowUser
{
    public function __construct(
        private readonly UserFollowRepository $userFollows,
    ) {}

    public function execute(UnfollowUserCommand $command): void
    {
        if ($command->followerUserId === $command->followedUserId) {
            throw new InvalidArgumentException('You cannot unfollow yourself.');
        }

        $this->userFollows->unfollow($command->followerUserId, $command->followedUserId);
    }
}
