<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\ReadModels\DashboardReadModel;
use App\Models\Team;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly DashboardReadModel $dashboard,
    ) {}

    public function __invoke(User $user): Response
    {
        $teams = $user->teams()
            ->orderBy('name')
            ->get()
            ->map(fn (Team $team): array => [
                'name' => $team->name,
                'slug' => $team->slug,
                'is_personal' => $team->is_personal,
                'engagement' => $this->dashboard->teamEngagementSummary($team->id),
                'recentSessions' => $this->dashboard->recentSessionsForTeam($team->id),
            ])
            ->all();

        return Inertia::render('admin/User', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'created_at' => $user->created_at?->toISOString(),
            ],
            'teams' => $teams,
        ]);
    }
}
