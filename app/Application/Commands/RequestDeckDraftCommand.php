<?php

declare(strict_types=1);

namespace App\Application\Commands;

/**
 * Creates the placeholder deck row up front so the AI build is inspectable and
 * the index can show a "building…" card straight away.
 */
readonly class RequestDeckDraftCommand
{
    public function __construct(
        public int $team_id,
        public string $name,
        public string $plan,
        /** The creating user's branding background, applied to the placeholder slide. */
        public ?string $background = null,
    ) {}
}
