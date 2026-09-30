<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class DeleteSessionCommand
{
    public function __construct(
        public int $sessionId,
    ) {}
}
