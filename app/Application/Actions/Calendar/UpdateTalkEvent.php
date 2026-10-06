<?php

declare(strict_types=1);

namespace App\Application\Actions\Calendar;

use App\Application\Commands\UpdateTalkEventCommand;
use App\Domain\Presentation\Contracts\TalkEventRepository;
use App\Domain\Presentation\Entities\TalkEventEntity;

class UpdateTalkEvent
{
    public function __construct(
        private readonly TalkEventRepository $events,
    ) {}

    public function execute(UpdateTalkEventCommand $command): TalkEventEntity
    {
        $event = $this->events->findById($command->talkEventId);

        $event->rename($command->name);
        $event->reschedule($command->date, $command->startTime);
        $event->attachTalk($command->talkId);

        return $this->events->save($event);
    }
}
