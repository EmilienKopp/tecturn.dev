<?php

namespace App\Http\Controllers\Presentations;

use App\Ai\HouseAllowance;
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

        // The Magic draft picker chooses the model: a null credential means the
        // free house model (rate limited); an id means the user's own key
        // (unlimited). The request rule guarantees the id belongs to this user.
        $credentialId = $request->validated('ai_credential_id');
        $credentialId = $credentialId === null ? null : (int) $credentialId;

        // Enforce the house limit before creating the draft row so a blocked
        // build leaves no orphan draft behind.
        if ($credentialId === null) {
            if (HouseAllowance::exceeded($user)) {
                $hours = (int) ceil(HouseAllowance::availableIn($user) / 3600);

                Inertia::flash('toast', [
                    'type' => 'error',
                    'message' => __('You have used all :max free deck builds. Add your own AI key in settings, or try again in about :hours h.', ['max' => HouseAllowance::max(), 'hours' => max(1, $hours)]),
                ]);

                return redirect()->back();
            }

            HouseAllowance::record($user);
        }

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
                ai_credential_id: $credentialId,
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
