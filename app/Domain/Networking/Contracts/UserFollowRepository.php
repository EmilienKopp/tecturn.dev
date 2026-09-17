<?php

declare(strict_types=1);

namespace App\Domain\Networking\Contracts;

interface UserFollowRepository
{
    public function follow(int $followerUserId, int $followedUserId): void;

    public function unfollow(int $followerUserId, int $followedUserId): void;

    public function isFollowing(int $followerUserId, int $followedUserId): bool;
}
