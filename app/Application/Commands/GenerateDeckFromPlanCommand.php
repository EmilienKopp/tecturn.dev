<?php

declare(strict_types=1);

namespace App\Application\Commands;

/**
 * Fills an already-requested draft with an AI-built deck. The plan is read from
 * the draft row (persisted by RequestDeckDraft), so it isn't carried here.
 */
readonly class GenerateDeckFromPlanCommand
{
    public function __construct(
        public int $presentation_id,
        public string $name,
        /** The creating user's branding; overrides the agent's theme so the deck stays on brand. */
        public ?array $branding = null,
    ) {}
}
