<?php

declare(strict_types=1);

namespace App\Application\Commands;

use DateTimeInterface;

readonly class ScheduleTalkEventCommand
{
    public function __construct(
        public int $teamId,
        public string $name,
        public DateTimeInterface $date,
        public ?string $startTime = null,
        public ?int $talkId = null,
    ) {}
}
