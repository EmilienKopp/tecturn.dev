<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class RequestRehearsalReviewCommand
{
    public function __construct(
        public int $rehearsalId,
        public int $requesterUserId,
        public int $reviewerUserId,
    ) {}
}
