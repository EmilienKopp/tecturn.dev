<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Application\Actions\Presentations\RecordPracticeRun;
use App\Application\Commands\RecordPracticeRunCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Presentations\RecordPracticeRunRequest;
use App\Infrastructure\ReadModels\ContactsReadModel;
use App\Infrastructure\ReadModels\PracticeRunReadModel;
use App\Infrastructure\ReadModels\RehearsalReviewReadModel;
use App\Models\PracticeRunModel;
use App\Models\PresentationModel;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PracticeRunController extends Controller
{
    public function __construct(
        private readonly PracticeRunReadModel $practiceRuns,
        private readonly RehearsalReviewReadModel $reviews,
        private readonly ContactsReadModel $contacts,
        private readonly RecordPracticeRun $recordPracticeRun,
    ) {}

    public function index(Request $request, Team $current_team): Response
    {
        return Inertia::render('rehearsals/Index', [
            'runs' => $this->practiceRuns->listForTeam($current_team->id),
            'reviewRequests' => $this->reviews->listForReviewer($request->user()->id),
        ]);
    }

    public function show(Request $request, Team $current_team, PracticeRunModel $practice_run): Response
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

    public function store(RecordPracticeRunRequest $request, Team $current_team, PresentationModel $presentation): RedirectResponse
    {
        Gate::authorize('view', $presentation);

        $audio = $request->file('audio');
        $audio = is_array($audio) ? null : $audio;

        $run = $this->recordPracticeRun->execute(new RecordPracticeRunCommand(
            presentationId: $presentation->id,
            teamId: $presentation->team_id,
            startedAt: $request->startedAt(),
            endedAt: $request->endedAt(),
            durationSeconds: (int) $request->validated('duration_seconds'),
            slideTimings: $request->slideTimings(),
            stepEvents: $request->stepEvents(),
            audioPath: $audio === null ? null : ($audio->getRealPath() ?: null),
            audioFileName: $audio !== null ? 'rehearsal.'.$this->extensionForMime($audio->getMimeType() ?? '') : null,
        ));

        return redirect()->route('rehearsals.show', [
            'current_team' => $current_team->slug,
            'practice_run' => $run->id,
        ]);
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'audio/ogg' => 'ogg',
            'audio/mp4', 'video/mp4' => 'm4a',
            default => 'webm',
        };
    }
}
