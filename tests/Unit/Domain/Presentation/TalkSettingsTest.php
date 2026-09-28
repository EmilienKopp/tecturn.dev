<?php

declare(strict_types=1);

use App\Domain\Presentation\ValueObjects\TalkSettings;

it('creates with defaults', function () {
    $settings = TalkSettings::defaults();

    expect($settings->showReactions)->toBeFalse()
        ->and($settings->showTranslation)->toBeTrue()
        ->and($settings->timerMode)->toBe('elapsed')
        ->and($settings->durationMinutes)->toBeNull()
        ->and($settings->reactions)->toBe(TalkSettings::DEFAULT_REACTIONS);
});

it('hydrates from array', function () {
    $settings = TalkSettings::fromArray([
        'showReactions' => true,
        'showTranslation' => false,
        'timerMode' => 'countdown',
        'durationMinutes' => 30,
    ]);

    expect($settings->showReactions)->toBeTrue()
        ->and($settings->showTranslation)->toBeFalse()
        ->and($settings->timerMode)->toBe('countdown')
        ->and($settings->durationMinutes)->toBe(30);
});

it('ignores unknown timerMode values and falls back to elapsed', function () {
    $settings = TalkSettings::fromArray(['timerMode' => 'invalid']);

    expect($settings->timerMode)->toBe('elapsed');
});

it('serialises to array', function () {
    $settings = new TalkSettings(showReactions: true, timerMode: 'countdown', durationMinutes: 45);

    expect($settings->toArray())->toMatchArray([
        'showReactions' => true,
        'timerMode' => 'countdown',
        'durationMinutes' => 45,
    ]);
});

it('roundtrips through fromArray and toArray', function () {
    $original = [
        'showReactions' => false,
        'showDock' => true,
        'showTranslation' => true,
        'timerMode' => 'elapsed',
        'durationMinutes' => null,
        'autoSave' => false,
        'footer' => [
            'enabled' => false,
            'xHandle' => null,
            'githubHandle' => null,
            'hashtag' => null,
            'bgColor' => 'transparent',
            'fontColor' => '#ffffff',
            'showInDock' => false,
        ],
        'reactions' => ['👏', '🔥', '🚀'],
        'allowFreeText' => true,
        'freeTextMaxLength' => 40,
    ];

    expect(TalkSettings::fromArray($original)->toArray())->toEqual($original);
});

it('clamps the free-text length into range and defaults when invalid', function () {
    expect(TalkSettings::fromArray(['freeTextMaxLength' => 999])->freeTextMaxLength)
        ->toBe(TalkSettings::MAX_FREE_TEXT_LENGTH)
        ->and(TalkSettings::fromArray(['freeTextMaxLength' => 0])->freeTextMaxLength)
        ->toBe(1)
        ->and(TalkSettings::fromArray(['freeTextMaxLength' => 'nope'])->freeTextMaxLength)
        ->toBe(TalkSettings::DEFAULT_FREE_TEXT_LENGTH)
        ->and(TalkSettings::defaults()->allowFreeText)->toBeFalse();
});

it('falls back to default reactions when the custom set is empty or invalid', function () {
    expect(TalkSettings::fromArray(['reactions' => []])->reactions)
        ->toBe(TalkSettings::DEFAULT_REACTIONS)
        ->and(TalkSettings::fromArray(['reactions' => 'nope'])->reactions)
        ->toBe(TalkSettings::DEFAULT_REACTIONS)
        ->and(TalkSettings::fromArray([])->reactions)
        ->toBe(TalkSettings::DEFAULT_REACTIONS);
});

it('trims, dedupes, and caps custom reactions', function () {
    $settings = TalkSettings::fromArray([
        'reactions' => [' 👏 ', '👏', '', 42, '🔥'],
    ]);

    expect($settings->reactions)->toBe(['👏', '🔥']);

    $tooMany = array_map(fn (int $i): string => "e{$i}", range(1, 15));

    expect(TalkSettings::fromArray(['reactions' => $tooMany])->reactions)
        ->toHaveCount(TalkSettings::MAX_REACTIONS);
});
