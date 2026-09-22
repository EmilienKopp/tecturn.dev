<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class AddReviewCommentCommand
{
    public function __construct(
        public int $rehearsalReviewId,
        public int $slideNumber,
        public string $message,
    ) {}
}
