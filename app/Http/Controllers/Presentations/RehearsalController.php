<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Application\Actions\Presentations\RecordRehearsal;
use App\Application\Commands\RecordRehearsalCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Presentations\RecordRehearsalRequest;
use App\Infrastructure\ReadModels\ContactsReadModel;
use App\Infrastructure\ReadModels\RehearsalReadModel;
use App\Infrastructure\ReadModels\RehearsalReviewReadModel;
use App\Models\PresentationModel;
use App\Models\RehearsalModel;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RehearsalController extends Controller
{
    public function __construct(
        private readonly RehearsalReadModel $rehearsals,
        private readonly RehearsalReviewReadModel $reviews,
        private readonly ContactsReadModel $contacts,
        private readonly RecordRehearsal $recordRehearsal,
    ) {}

    public function index(Request $request, Team $current_team): Response
    {
        return Inertia::render('rehearsals/Index', [
            'runs' => $this->rehearsals->listForTeam($current_team->id),
            'reviewRequests' => $this->reviews->listForReviewer($request->user()->id),
        ]);
    }

    public function show(Request $request, Team $current_team, RehearsalModel $rehearsal): Response
    {
        $run = $this->rehearsals->findForReplay($rehearsal->id);

        return Inertia::render('rehearsals/Show', [
            'run' => $run,
            'reviews' => $this->reviews->listForRun($rehearsal->id),
            'followers' => $this->contacts->followersForUser($request->user()->id),
            'audioUrl' => $run['has_recording']
                ? route('rehearsals.audio', ['rehearsal' => $rehearsal->id])
                : null,
        ]);
    }

    public function store(RecordRehearsalRequest $request, Team $current_team, PresentationModel $presentation): RedirectResponse
    {
        Gate::authorize('view', $presentation);

        $audio = $request->file('audio');
        $audio = is_array($audio) ? null : $audio;

        $run = $this->recordRehearsal->execute(new RecordRehearsalCommand(
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
            'rehearsal' => $run->id,
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
