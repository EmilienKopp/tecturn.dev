<?php

declare(strict_types=1);

namespace App\Domain\Presentation\ValueObjects;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * What the presenter's own history says about their delivery of a deck:
 * rehearsals plus finished live sessions, boiled down to the few numbers the
 * talk-length dialog shows next to the linter's estimate. Averages are null
 * until there is at least one measured run.
 */
#[TypeScript]
readonly class DeliveryStats
{
    public function __construct(
        public int $rehearsalCount = 0,
        public int $sessionCount = 0,
        public int $totalSpokenSeconds = 0,
        public ?int $avgRunSeconds = null,
        public ?int $avgSecondsPerSlide = null,
        public ?int $avgWordsPerMinute = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'rehearsalCount' => $this->rehearsalCount,
            'sessionCount' => $this->sessionCount,
            'totalSpokenSeconds' => $this->totalSpokenSeconds,
            'avgRunSeconds' => $this->avgRunSeconds,
            'avgSecondsPerSlide' => $this->avgSecondsPerSlide,
            'avgWordsPerMinute' => $this->avgWordsPerMinute,
        ];
    }
}
