<?php

declare(strict_types=1);

namespace App\Application\Commands;

use DateTimeInterface;

readonly class UpdateTalkEventCommand
{
    public function __construct(
        public int $talkEventId,
        public string $name,
        public DateTimeInterface $date,
        public ?string $startTime = null,
        public ?int $talkId = null,
    ) {}
}
