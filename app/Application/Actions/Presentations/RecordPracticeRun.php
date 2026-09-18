<?php

declare(strict_types=1);

namespace App\Application\Actions\Presentations;

use App\Application\Commands\RecordPracticeRunCommand;
use App\Domain\Presentation\Contracts\PracticeRunRepository;
use App\Domain\Presentation\Contracts\PresentationRepository;
use App\Domain\Presentation\Entities\PracticeRunEntity;

class RecordPracticeRun
{
    public function __construct(
        private readonly PracticeRunRepository $practiceRuns,
        private readonly PresentationRepository $presentations,
    ) {}

    /**
     * Persists a finished rehearsal together with a frozen copy of the deck,
     * so the run can be replayed later exactly as it was practiced.
     */
    public function execute(RecordPracticeRunCommand $command): PracticeRunEntity
    {
        $presentation = $this->presentations->findById($command->presentationId);

        $run = $this->practiceRuns->save(new PracticeRunEntity(
            presentation_id: $command->presentationId,
            team_id: $command->teamId,
            started_at: $command->startedAt,
            ended_at: $command->endedAt,
            duration_seconds: $command->durationSeconds,
            slide_timings: $command->slideTimings,
            content: $presentation->content->toArray(),
            flow: $presentation->flow?->toArray(),
            step_events: $command->stepEvents,
        ));

        if ($command->audioPath !== null && $command->audioFileName !== null && $run->id !== null) {
            $this->practiceRuns->storeRecording($run->id, $command->audioPath, $command->audioFileName);
        }

        return $run;
    }
}
