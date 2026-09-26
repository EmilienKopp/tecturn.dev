<?php

namespace App\Http\Controllers\Presentations;

use App\Application\Actions\Presentations\RequestDeckDraft;
use App\Application\Commands\GenerateDeckFromPlanCommand;
use App\Application\Commands\RequestDeckDraftCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Presentations\GenerateDeckRequest;
use App\Jobs\GenerateDeckJob;
use App\Models\Presentation;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class GenerateDeckController extends Controller
{
    public function __invoke(GenerateDeckRequest $request, Team $current_team, RequestDeckDraft $requestDraft): RedirectResponse
    {
        Gate::authorize('create', [Presentation::class, $current_team]);

        $user = $request->user();
        $name = (string) $request->validated('name', '');

        // Persist the draft row before queueing so the build is inspectable and
        // the index can render a "building…" card straight away.
        $presentation = $requestDraft->execute(
            new RequestDeckDraftCommand(
                team_id: $current_team->id,
                name: $name,
                plan: $request->validated('plan'),
                background: $user->branding['background'] ?? null,
            ),
        );

        GenerateDeckJob::dispatch(
            new GenerateDeckFromPlanCommand(
                presentation_id: $presentation->id,
                name: $name,
                branding: $user->branding,
            ),
            $user->id,
            $current_team->slug,
        );

        Inertia::flash('toast', [
            'type' => 'info',
            'message' => __("We're building your deck. You'll get a notification when it's ready."),
        ]);

        return redirect()->back();
    }
}
