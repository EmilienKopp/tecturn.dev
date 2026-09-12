<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class RejectBetaRequestCommand
{
    public function __construct(
        public int $betaRequestId,
    ) {}
}
