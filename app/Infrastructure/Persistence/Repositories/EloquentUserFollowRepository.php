<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Networking\Contracts\UserFollowRepository;
use App\Enums\FollowStatus;
use Illuminate\Support\Facades\DB;

class EloquentUserFollowRepository implements UserFollowRepository
{
    public function follow(int $followerUserId, int $followedUserId): void
    {
        // insertOrIgnore so re-requesting neither refreshes the timestamp nor
        // downgrades an already accepted follow back to pending.
        DB::table('user_follows')->insertOrIgnore([
            'follower_user_id' => $followerUserId,
            'followed_user_id' => $followedUserId,
            'status' => FollowStatus::Pending->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function unfollow(int $followerUserId, int $followedUserId): void
    {
        DB::table('user_follows')
            ->where('follower_user_id', $followerUserId)
            ->where('followed_user_id', $followedUserId)
            ->delete();
    }

    public function accept(int $followerUserId, int $followedUserId): void
    {
        DB::table('user_follows')
            ->where('follower_user_id', $followerUserId)
            ->where('followed_user_id', $followedUserId)
            ->where('status', FollowStatus::Pending->value)
            ->update([
                'status' => FollowStatus::Accepted->value,
                'updated_at' => now(),
            ]);
    }

    public function isFollowing(int $followerUserId, int $followedUserId): bool
    {
        return DB::table('user_follows')
            ->where('follower_user_id', $followerUserId)
            ->where('followed_user_id', $followedUserId)
            ->where('status', FollowStatus::Accepted->value)
            ->exists();
    }

    public function hasPendingRequest(int $followerUserId, int $followedUserId): bool
    {
        return DB::table('user_follows')
            ->where('follower_user_id', $followerUserId)
            ->where('followed_user_id', $followedUserId)
            ->where('status', FollowStatus::Pending->value)
            ->exists();
    }
}
