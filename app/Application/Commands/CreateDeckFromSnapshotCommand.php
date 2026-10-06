<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class CreateDeckFromSnapshotCommand
{
    public function __construct(
        public int $rehearsalId,
    ) {}
}
