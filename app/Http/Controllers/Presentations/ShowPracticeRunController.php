<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Http\Controllers\Controller;
use App\Infrastructure\ReadModels\ContactsReadModel;
use App\Infrastructure\ReadModels\PracticeRunReadModel;
use App\Infrastructure\ReadModels\RehearsalReviewReadModel;
use App\Models\PracticeRunModel;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowPracticeRunController extends Controller
{
    public function __construct(
        private readonly PracticeRunReadModel $practiceRuns,
        private readonly RehearsalReviewReadModel $reviews,
        private readonly ContactsReadModel $contacts,
    ) {}

    public function __invoke(Request $request, Team $current_team, PracticeRunModel $practice_run): Response
    {
        $run = $this->practiceRuns->findForReplay($practice_run->id);

        return Inertia::render('rehearsals/Show', [
            'run' => $run,
            'reviews' => $this->reviews->listForRun($practice_run->id),
            'followers' => $this->contacts->followersForUser($request->user()->id),
            'audioUrl' => $run['has_recording']
                ? route('rehearsals.audio', ['practice_run' => $practice_run->id])
                : null,
        ]);
    }
}
