<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class CreateDeckVersionCommand
{
    /**
     * @param  'major'|'minor'  $bump
     */
    public function __construct(
        public int $presentationId,
        public string $bump,
    ) {}
}
