<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class RequestBetaAccessCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $message = null,
    ) {}
}
