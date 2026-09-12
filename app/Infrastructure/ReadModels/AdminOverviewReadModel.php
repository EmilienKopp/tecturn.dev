<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModels;

use App\Models\Views\AdminOverviewView;

class AdminOverviewReadModel
{
    /**
     * Platform-wide roll-up for the admin dashboard.
     *
     * @return array{
     *     total_users: int,
     *     total_teams: int,
     *     total_workspaces: int,
     *     total_presentations: int,
     *     total_sessions: int,
     *     live_sessions: int,
     *     total_reactions: int,
     *     total_viewers: int
     * }
     */
    public function summary(): array
    {
        $row = AdminOverviewView::query()->first();

        return [
            'total_users' => $row?->total_users ?? 0,
            'total_teams' => $row?->total_teams ?? 0,
            'total_workspaces' => $row?->total_workspaces ?? 0,
            'total_presentations' => $row?->total_presentations ?? 0,
            'total_sessions' => $row?->total_sessions ?? 0,
            'live_sessions' => $row?->live_sessions ?? 0,
            'total_reactions' => $row?->total_reactions ?? 0,
            'total_viewers' => $row?->total_viewers ?? 0,
        ];
    }
}
