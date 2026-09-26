<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class SubmitFeedbackCommand
{
    public function __construct(
        public string $message,
        public ?int $userId = null,
    ) {}
}
