<?php

declare(strict_types=1);

namespace App\Application\Actions\Networking;

use App\Application\Commands\FollowUserCommand;
use App\Domain\Networking\Contracts\UserFollowRepository;
use InvalidArgumentException;

class FollowUser
{
    public function __construct(
        private readonly UserFollowRepository $userFollows,
    ) {}

    public function execute(FollowUserCommand $command): void
    {
        if ($command->followerUserId === $command->followedUserId) {
            throw new InvalidArgumentException('You cannot follow yourself.');
        }

        $this->userFollows->follow($command->followerUserId, $command->followedUserId);
    }
}
