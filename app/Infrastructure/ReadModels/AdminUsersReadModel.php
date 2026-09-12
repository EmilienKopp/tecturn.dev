<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModels;

use App\Models\Views\AdminUserView;

class AdminUsersReadModel
{
    /**
     * Every registered user, newest first, shaped for the admin user list.
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     avatar: string,
     *     team_count: int,
     *     current_team_name: string|null,
     *     created_at: string|null
     * }>
     */
    public function all(): array
    {
        return AdminUserView::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (AdminUserView $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'team_count' => $user->team_count,
                'current_team_name' => $user->current_team_name,
                'created_at' => $user->created_at?->toISOString(),
            ])
            ->all();
    }
}
