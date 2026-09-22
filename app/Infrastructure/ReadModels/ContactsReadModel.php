<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModels;

use App\Enums\FollowStatus;
use App\Models\Views\ContactProfileView;
use App\Models\Views\ContactRelationshipView;
use App\Models\Views\ContactTalkView;
use Illuminate\Database\Eloquent\Builder;

class ContactsReadModel
{
    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     avatar: string,
     *     handle: string|null,
     *     social_x_handle: string|null,
     *     social_github_handle: string|null,
     *     talks_count: int,
     *     total_viewers: int,
     *     total_reactions: int,
     *     followers_count: int,
     *     following_count: int,
     *     follow_status: string
     * }>
     */
    public function directoryForUser(int $userId, ?string $search = null, int $limit = 20): array
    {
        $followStatuses = $this->followStatuses($userId);
        $term = $this->normalizedSearch($search);

        return ContactProfileView::query()
            ->where('id', '!=', $userId)
            ->when($term === null, function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query
                        ->whereRaw("COALESCE(handle, '') != ''")
                        ->orWhereRaw("COALESCE(social_x_handle, '') != ''")
                        ->orWhereRaw("COALESCE(social_github_handle, '') != ''");
                });
            })
            ->when($term !== null, function (Builder $query) use ($term): void {
                $like = '%'.$term.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query
                        ->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw("LOWER(COALESCE(handle, '')) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(social_x_handle, '')) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(social_github_handle, '')) LIKE ?", [$like]);
                });
            })
            ->orderByDesc('talks_count')
            ->orderByDesc('followers_count')
            ->orderByRaw('LOWER(name)')
            ->limit($limit)
            ->get()
            ->map(fn (ContactProfileView $profile): array => $this->mapProfile($profile, $followStatuses[$profile->id] ?? 'none'))
            ->all();
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     avatar: string,
     *     handle: string|null,
     *     social_x_handle: string|null,
     *     social_github_handle: string|null,
     *     followed_at: string|null
     * }>
     */
    public function followingForUser(int $userId): array
    {
        return ContactRelationshipView::query()
            ->where('follower_user_id', $userId)
            ->where('status', FollowStatus::Accepted->value)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ContactRelationshipView $relationship): array => [
                'id' => $relationship->followed_user_id,
                'name' => $relationship->followed_name,
                'avatar' => $relationship->followed_avatar,
                'handle' => $relationship->followed_handle,
                'social_x_handle' => $relationship->followed_social_x_handle,
                'social_github_handle' => $relationship->followed_social_github_handle,
                'followed_at' => $relationship->created_at?->toISOString(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     avatar: string,
     *     handle: string|null,
     *     social_x_handle: string|null,
     *     social_github_handle: string|null,
     *     followed_at: string|null
     * }>
     */
    public function followersForUser(int $userId): array
    {
        return ContactRelationshipView::query()
            ->where('followed_user_id', $userId)
            ->where('status', FollowStatus::Accepted->value)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ContactRelationshipView $relationship): array => [
                'id' => $relationship->follower_user_id,
                'name' => $relationship->follower_name,
                'avatar' => $relationship->follower_avatar,
                'handle' => $relationship->follower_handle,
                'social_x_handle' => $relationship->follower_social_x_handle,
                'social_github_handle' => $relationship->follower_social_github_handle,
                'followed_at' => $relationship->created_at?->toISOString(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     updated_at: string|null,
     *     last_presented_at: string|null,
     *     viewer_count: int,
     *     reaction_total: int,
     *     user: array{
     *         id: int,
     *         name: string,
     *         avatar: string,
     *         handle: string|null,
     *         social_x_handle: string|null,
     *         social_github_handle: string|null
     *     }
     * }>
     */
    public function talksFromPeopleUserFollows(int $userId, int $limit = 12): array
    {
        $followingIds = $this->followingIds($userId);

        if ($followingIds === []) {
            return [];
        }

        return ContactTalkView::query()
            ->whereIn('user_id', $followingIds)
            ->orderByRaw('COALESCE(last_presented_at, updated_at) DESC')
            ->limit($limit)
            ->get()
            ->map(fn (ContactTalkView $talk): array => [
                'id' => $talk->id,
                'name' => $talk->name,
                'updated_at' => $talk->updated_at?->toISOString(),
                'last_presented_at' => $talk->last_presented_at?->toISOString(),
                'viewer_count' => $talk->viewer_count,
                'reaction_total' => $talk->reaction_total,
                'user' => [
                    'id' => $talk->user_id,
                    'name' => $talk->user_name,
                    'avatar' => $talk->user_avatar,
                    'handle' => $talk->user_handle,
                    'social_x_handle' => $talk->user_social_x_handle,
                    'social_github_handle' => $talk->user_social_github_handle,
                ],
            ])
            ->all();
    }

    /**
     * People who asked to follow this user and are waiting on an answer.
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     avatar: string,
     *     handle: string|null,
     *     social_x_handle: string|null,
     *     social_github_handle: string|null,
     *     requested_at: string|null
     * }>
     */
    public function followRequestsForUser(int $userId): array
    {
        return ContactRelationshipView::query()
            ->where('followed_user_id', $userId)
            ->where('status', FollowStatus::Pending->value)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ContactRelationshipView $relationship): array => [
                'id' => $relationship->follower_user_id,
                'name' => $relationship->follower_name,
                'avatar' => $relationship->follower_avatar,
                'handle' => $relationship->follower_handle,
                'social_x_handle' => $relationship->follower_social_x_handle,
                'social_github_handle' => $relationship->follower_social_github_handle,
                'requested_at' => $relationship->created_at?->toISOString(),
            ])
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function followingIds(int $userId): array
    {
        return ContactRelationshipView::query()
            ->where('follower_user_id', $userId)
            ->where('status', FollowStatus::Accepted->value)
            ->pluck('followed_user_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * The signed-in user's outgoing follow rows, keyed by followed user id.
     *
     * @return array<int, string>
     */
    private function followStatuses(int $userId): array
    {
        return ContactRelationshipView::query()
            ->where('follower_user_id', $userId)
            ->pluck('status', 'followed_user_id')
            ->mapWithKeys(fn (mixed $status, mixed $id): array => [(int) $id => (string) $status])
            ->all();
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     avatar: string,
     *     handle: string|null,
     *     social_x_handle: string|null,
     *     social_github_handle: string|null,
     *     talks_count: int,
     *     total_viewers: int,
     *     total_reactions: int,
     *     followers_count: int,
     *     following_count: int,
     *     follow_status: string
     * }
     */
    private function mapProfile(ContactProfileView $profile, string $followStatus): array
    {
        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'avatar' => $profile->avatar,
            'handle' => $profile->handle,
            'social_x_handle' => $profile->social_x_handle,
            'social_github_handle' => $profile->social_github_handle,
            'talks_count' => $profile->talks_count,
            'total_viewers' => $profile->total_viewers,
            'total_reactions' => $profile->total_reactions,
            'followers_count' => $profile->followers_count,
            'following_count' => $profile->following_count,
            'follow_status' => $followStatus,
        ];
    }

    private function normalizedSearch(?string $search): ?string
    {
        if (! is_string($search)) {
            return null;
        }

        $term = ltrim(mb_strtolower(trim($search)), '@');

        return $term === '' ? null : $term;
    }
}
