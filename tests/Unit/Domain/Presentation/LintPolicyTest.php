<?php

declare(strict_types=1);

use App\Domain\Presentation\ValueObjects\LintPolicy;

it('exposes sensible defaults', function () {
    $policy = LintPolicy::defaults();

    expect($policy->wordsPerMinute)->toBe(130)
        ->and($policy->cjkCharsPerMinute)->toBe(300)
        ->and($policy->wordsGood)->toBe(40)
        ->and($policy->wordsMax)->toBe(75)
        ->and($policy->cjkCharsGood)->toBe(90)
        ->and($policy->cjkCharsMax)->toBe(170)
        ->and($policy->paceUnderRatio)->toBe(0.85)
        ->and($policy->paceOverRatio)->toBe(1.0);
});

it('builds from an array and falls back per field', function () {
    $policy = LintPolicy::fromArray([
        'wordsPerMinute' => 150,
        'wordsGood' => 30,
        'paceOverRatio' => 1.2,
    ]);

    expect($policy->wordsPerMinute)->toBe(150)
        ->and($policy->wordsGood)->toBe(30)
        ->and($policy->paceOverRatio)->toBe(1.2)
        // Untouched fields keep their defaults.
        ->and($policy->cjkCharsGood)->toBe(90)
        ->and($policy->paceUnderRatio)->toBe(0.85);
});

it('round-trips through toArray', function () {
    $policy = LintPolicy::fromArray([
        'wordsPerMinute' => 120,
        'cjkCharsMax' => 200,
    ]);

    expect(LintPolicy::fromArray($policy->toArray()))->toEqual($policy);
});
