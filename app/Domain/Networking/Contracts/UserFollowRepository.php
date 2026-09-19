<?php

declare(strict_types=1);

namespace App\Domain\Networking\Contracts;

interface UserFollowRepository
{
    /**
     * Records a follow request. Does nothing when a request or an accepted
     * follow already exists.
     */
    public function follow(int $followerUserId, int $followedUserId): void;

    /**
     * Removes the follow row, whatever its status: unfollow, cancel a
     * pending request, or reject one.
     */
    public function unfollow(int $followerUserId, int $followedUserId): void;

    public function accept(int $followerUserId, int $followedUserId): void;

    public function isFollowing(int $followerUserId, int $followedUserId): bool;

    public function hasPendingRequest(int $followerUserId, int $followedUserId): bool;
}
