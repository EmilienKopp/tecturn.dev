<?php

declare(strict_types=1);

namespace App\Application\Commands;

use DateTimeInterface;

readonly class RecordPracticeRunCommand
{
    /**
     * @param  list<array{slide: int, seconds: int}>  $slideTimings
     */
    public function __construct(
        public int $presentationId,
        public int $teamId,
        public DateTimeInterface $startedAt,
        public DateTimeInterface $endedAt,
        public int $durationSeconds,
        public array $slideTimings,
    ) {}
}
