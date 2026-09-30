<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Content linter policy
|--------------------------------------------------------------------------
| Authoritative values for the presentation content linter. They are carried
| by App\Domain\Presentation\ValueObjects\LintPolicy, shared to the frontend
| via HandleInertiaRequests, and consumed by the editor's lint.ts. This file
| is the single source of truth — the frontend never hardcodes these numbers.
*/

return [
    // Delivery speed used for the speaking-time estimate.
    'wordsPerMinute' => 130,
    'cjkCharsPerMinute' => 300,

    // Per-slide density ceilings. `good` is the comfortable target; `max` is
    // the wall-of-text line. Between them the slide gets a gentle warning.
    'wordsGood' => 40,
    'wordsMax' => 75,
    'cjkCharsGood' => 90,
    'cjkCharsMax' => 170,

    // How far the estimated deck length may drift from the target duration
    // before the pace reads under/over (fractions of the target).
    'paceUnderRatio' => 0.85,
    'paceOverRatio' => 1.0,

    // Speaking-time multiplier per presentation style (TalkSettings). The
    // word-count estimate assumes every on-screen word is narrated verbatim —
    // that's the text-heavy baseline. Sparser decks carry far more talk per
    // word on screen.
    'styleMultipliers' => [
        'minimalistic' => 6.0,
        'balanced' => 3.0,
        'text-heavy' => 1.0,
    ],
];
