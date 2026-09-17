<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class CompleteRehearsalReviewCommand
{
    public function __construct(
        public int $rehearsalReviewId,
    ) {}
}
