<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class GenerateDeckFromPlanCommand
{
    /**
     * @param  array<string, mixed>|null  $branding  The creating user's branding; overrides the
     *                                               agent's theme so the deck stays on brand.
     */
    public function __construct(
        public int $team_id,
        public string $name,
        public string $plan,
        public ?array $branding = null,
    ) {}
}
