<?php

namespace App\Http\Controllers\Presentations;

use App\Application\Commands\GenerateDeckFromPlanCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Presentations\GenerateDeckRequest;
use App\Jobs\GenerateDeckJob;
use App\Models\PresentationModel;
use App\Models\Team;
use App\Presentation\GeneratingDeckTally;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class GenerateDeckController extends Controller
{
    public function __invoke(GenerateDeckRequest $request, Team $current_team, GeneratingDeckTally $tally): RedirectResponse
    {
        Gate::authorize('create', [PresentationModel::class, $current_team]);

        GenerateDeckJob::dispatch(
            new GenerateDeckFromPlanCommand(
                team_id: $current_team->id,
                name: (string) $request->validated('name', ''),
                plan: $request->validated('plan'),
                branding: $request->user()->branding,
            ),
            $request->user()->id,
            $current_team->slug,
        );

        // Surface a "building…" skeleton on the index straight away.
        $tally->increment($current_team->id);

        Inertia::flash('toast', [
            'type' => 'info',
            'message' => __("We're building your deck. You'll get a notification when it's ready."),
        ]);

        return redirect()->back();
    }
}
