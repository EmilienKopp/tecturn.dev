<?php

declare(strict_types=1);

namespace App\Domain\Presentation\ValueObjects;

use Splitstack\Typewriter\Attributes\TypeScript;

/**
 * Thresholds that govern the content linter: delivery speed for the
 * speaking-time estimate, per-slide density ceilings, and the pace tolerance
 * against the talk target. The authoritative values live in config/lint.php;
 * this value object is the typed carrier shared to the frontend, where the
 * editor's lint.ts reads them instead of hardcoding its own.
 */
#[TypeScript]
readonly class LintPolicy
{
    public function __construct(
        public int $wordsPerMinute = 130,
        public int $cjkCharsPerMinute = 300,
        public int $wordsGood = 40,
        public int $wordsMax = 75,
        public int $cjkCharsGood = 90,
        public int $cjkCharsMax = 170,
        public float $paceUnderRatio = 0.85,
        public float $paceOverRatio = 1.0,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $defaults = new self;

        return new self(
            wordsPerMinute: (int) ($data['wordsPerMinute'] ?? $defaults->wordsPerMinute),
            cjkCharsPerMinute: (int) ($data['cjkCharsPerMinute'] ?? $defaults->cjkCharsPerMinute),
            wordsGood: (int) ($data['wordsGood'] ?? $defaults->wordsGood),
            wordsMax: (int) ($data['wordsMax'] ?? $defaults->wordsMax),
            cjkCharsGood: (int) ($data['cjkCharsGood'] ?? $defaults->cjkCharsGood),
            cjkCharsMax: (int) ($data['cjkCharsMax'] ?? $defaults->cjkCharsMax),
            paceUnderRatio: (float) ($data['paceUnderRatio'] ?? $defaults->paceUnderRatio),
            paceOverRatio: (float) ($data['paceOverRatio'] ?? $defaults->paceOverRatio),
        );
    }

    public static function defaults(): self
    {
        return new self;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'wordsPerMinute' => $this->wordsPerMinute,
            'cjkCharsPerMinute' => $this->cjkCharsPerMinute,
            'wordsGood' => $this->wordsGood,
            'wordsMax' => $this->wordsMax,
            'cjkCharsGood' => $this->cjkCharsGood,
            'cjkCharsMax' => $this->cjkCharsMax,
            'paceUnderRatio' => $this->paceUnderRatio,
            'paceOverRatio' => $this->paceOverRatio,
        ];
    }
}
