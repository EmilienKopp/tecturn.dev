<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class SetFeatureFlagCommand
{
    public function __construct(
        public string $key,
        public bool|string $value,
        public ?int $teamId = null,
    ) {}
}
