<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class RespondToFollowRequestCommand
{
    public function __construct(
        public int $followerUserId,
        public int $followedUserId,
    ) {}
}
