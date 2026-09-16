<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Application\Actions\Presentations\RecordPracticeRun;
use App\Application\Commands\RecordPracticeRunCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Presentations\RecordPracticeRunRequest;
use App\Models\PresentationModel;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RecordPracticeRunController extends Controller
{
    public function __construct(private readonly RecordPracticeRun $recordPracticeRun) {}

    public function __invoke(RecordPracticeRunRequest $request, Team $current_team, PresentationModel $presentation): RedirectResponse
    {
        Gate::authorize('view', $presentation);

        $run = $this->recordPracticeRun->execute(new RecordPracticeRunCommand(
            presentationId: $presentation->id,
            teamId: $presentation->team_id,
            startedAt: $request->startedAt(),
            endedAt: $request->endedAt(),
            durationSeconds: (int) $request->validated('duration_seconds'),
            slideTimings: $request->slideTimings(),
        ));

        return redirect()->route('rehearsals.show', [
            'current_team' => $current_team->slug,
            'practice_run' => $run->id,
        ]);
    }
}
