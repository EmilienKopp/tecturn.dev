<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class RetryDeckDraftCommand
{
    public function __construct(
        public int $presentation_id,
    ) {}
}
