<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class DeleteTalkEventCommand
{
    public function __construct(
        public int $talkEventId,
    ) {}
}
