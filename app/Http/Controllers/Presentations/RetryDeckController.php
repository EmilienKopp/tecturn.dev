<?php

namespace App\Http\Controllers\Presentations;

use App\Application\Actions\Presentations\RequestDeckDraft;
use App\Application\Actions\Presentations\RetryDeckDraft;
use App\Application\Commands\GenerateDeckFromPlanCommand;
use App\Application\Commands\RetryDeckDraftCommand;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateDeckJob;
use App\Models\PresentationModel;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class RetryDeckController extends Controller
{
    public function __invoke(Team $current_team, PresentationModel $presentation, RetryDeckDraft $retryDraft): RedirectResponse
    {
        Gate::authorize('update', $presentation);

        $entity = $retryDraft->execute(
            new RetryDeckDraftCommand(presentation_id: $presentation->id),
        );

        $user = request()->user();

        // Keep a user-chosen name; a placeholder name is replaced by the AI title.
        $name = $entity->name === RequestDeckDraft::GENERATING_NAME ? '' : $entity->name;

        GenerateDeckJob::dispatch(
            new GenerateDeckFromPlanCommand(
                presentation_id: $entity->id,
                name: $name,
                branding: $user->branding,
            ),
            $user->id,
            $current_team->slug,
        );

        Inertia::flash('toast', [
            'type' => 'info',
            'message' => __("We're rebuilding your deck. You'll get a notification when it's ready."),
        ]);

        return redirect()->back();
    }
}
