<?php

declare(strict_types=1);

namespace App\Application\Commands;

/**
 * Fills an already-requested draft with an AI-built deck. The plan is read from
 * the draft row (persisted by RequestDeckDraft), so it isn't carried here.
 */
readonly class GenerateDeckFromPlanCommand
{
    /**
     * @param  array<string, mixed>|null  $branding  The creating user's branding; overrides the
     *                                               agent's theme so the deck stays on brand.
     */
    public function __construct(
        public int $presentation_id,
        public string $name,
        /** The creating user's branding; overrides the agent's theme so the deck stays on brand. */
        public ?array $branding = null,
        /**
         * The user's chosen "bring your own AI" credential id, or null to use
         * the house provider. Only the id travels on the queue payload; the
         * key is decrypted in the job so it never lands in the jobs table.
         */
        public ?int $ai_credential_id = null,
    ) {}
}
