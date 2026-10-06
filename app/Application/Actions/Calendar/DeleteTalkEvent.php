<?php

declare(strict_types=1);

namespace App\Application\Actions\Calendar;

use App\Application\Commands\DeleteTalkEventCommand;
use App\Domain\Presentation\Contracts\TalkEventRepository;

class DeleteTalkEvent
{
    public function __construct(
        private readonly TalkEventRepository $events,
    ) {}

    public function execute(DeleteTalkEventCommand $command): void
    {
        $this->events->deleteById($command->talkEventId);
    }
}
