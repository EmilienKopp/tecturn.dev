<?php

declare(strict_types=1);

namespace App\Application\Actions\Calendar;

use App\Application\Commands\ScheduleTalkEventCommand;
use App\Domain\Presentation\Contracts\TalkEventRepository;
use App\Domain\Presentation\Entities\TalkEventEntity;

class ScheduleTalkEvent
{
    public function __construct(
        private readonly TalkEventRepository $events,
    ) {}

    public function execute(ScheduleTalkEventCommand $command): TalkEventEntity
    {
        return $this->events->save(new TalkEventEntity(
            team_id: $command->teamId,
            name: $command->name,
            date: $command->date,
            start_time: $command->startTime,
            talk_id: $command->talkId,
        ));
    }
}
