<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class MarkDraftFailedCommand
{
    public function __construct(
        public int $presentation_id,
        public string $error,
    ) {}
}
