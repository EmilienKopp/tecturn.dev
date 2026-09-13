<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Support\FeatureFlags\FeatureFlagsPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminFeatureFlagsController extends Controller
{
    public function __construct(private readonly FeatureFlagsPresenter $presenter) {}

    public function __invoke(Request $request): Response
    {
        $selectedTeam = ($teamId = $request->integer('team_id'))
            ? Team::find($teamId)
            : null;

        return Inertia::render('admin/FeatureFlags', [
            'globalFlags' => $this->presenter->globalFlags(),
            'teams' => Team::query()
                ->orderBy('name')
                ->get(['id', 'name', 'is_personal'])
                ->map(fn (Team $team): array => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'is_personal' => $team->is_personal,
                ]),
            'selectedTeamId' => $selectedTeam?->id,
            'teamFlags' => $selectedTeam ? $this->presenter->teamFlags($selectedTeam) : [],
        ]);
    }
}
