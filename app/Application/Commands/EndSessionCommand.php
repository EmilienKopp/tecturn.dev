<?php

declare(strict_types=1);

namespace App\Application\Commands;

use DateTimeInterface;

readonly class EndSessionCommand
{
    /**
     * @param  list<array{slide: int, seconds: int}>|null  $slideTimings
     * @param  list<array{slide: int, reactions: array<string, int>}>|null  $reactionSlides
     */
    public function __construct(
        public int $presentationId,
        public DateTimeInterface $endedAt,
        public ?array $slideTimings = null,
        public ?array $reactionSlides = null,
    ) {}
}
