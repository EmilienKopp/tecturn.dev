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
