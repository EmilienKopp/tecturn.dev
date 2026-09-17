<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Networking\Contracts\UserFollowRepository;
use Illuminate\Support\Facades\DB;

class EloquentUserFollowRepository implements UserFollowRepository
{
    public function follow(int $followerUserId, int $followedUserId): void
    {
        DB::table('user_follows')->updateOrInsert(
            [
                'follower_user_id' => $followerUserId,
                'followed_user_id' => $followedUserId,
            ],
            [
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function unfollow(int $followerUserId, int $followedUserId): void
    {
        DB::table('user_follows')
            ->where('follower_user_id', $followerUserId)
            ->where('followed_user_id', $followedUserId)
            ->delete();
    }
}
