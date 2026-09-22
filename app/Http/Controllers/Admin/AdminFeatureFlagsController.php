<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Actions\Features\SetFeatureFlag;
use App\Application\Commands\SetFeatureFlagCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFeatureFlagRequest;
use App\Models\Team;
use App\Support\FeatureFlags\FeatureFlagsPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminFeatureFlagsController extends Controller
{
    public function __construct(
        private readonly FeatureFlagsPresenter $presenter,
        private readonly SetFeatureFlag $setFeatureFlag,
    ) {}

    public function index(Request $request): Response
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

    public function update(UpdateFeatureFlagRequest $request): RedirectResponse
    {
        $this->setFeatureFlag->execute(new SetFeatureFlagCommand(
            key: $request->validated('key'),
            value: $request->resolvedValue(),
            teamId: $request->teamId(),
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Feature updated.')]);

        return back();
    }
}
