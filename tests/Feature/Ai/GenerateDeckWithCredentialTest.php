<?php

declare(strict_types=1);

use App\Ai\Agents\Deckster;
use App\Application\Actions\Presentations\GenerateDeckFromPlan;
use App\Application\Actions\Presentations\RequestDeckDraft;
use App\Application\Commands\GenerateDeckFromPlanCommand;
use App\Application\Commands\RequestDeckDraftCommand;
use App\Models\Team;
use App\Models\User;
use App\Models\UserAiCredential;

/** Local helpers, so this file runs standalone or as part of the suite. */
function credDraft(Team $team, string $plan = '# My plan'): int
{
    return app(RequestDeckDraft::class)->execute(
        new RequestDeckDraftCommand(team_id: $team->id, name: '', plan: $plan),
    )->id;
}

function credFakeDeck(): array
{
    return [
        'title' => 'Intro',
        'slides' => [
            [
                'layout' => 'center',
                'title' => 'Title',
                'blocks' => [
                    ['slot' => 'main', 'type' => 'text', 'content' => 'Intro'],
                ],
            ],
        ],
    ];
}

test('a deck build uses the user AI credential, pointing the SDK at their key', function () {
    Deckster::fake([credFakeDeck()]);

    $team = Team::factory()->create();
    $user = User::factory()->create();
    $draftId = credDraft($team);

    $credential = UserAiCredential::factory()->for($user)->create([
        'driver' => 'anthropic',
        'model' => 'claude-opus-4-8',
        'api_key' => 'user-supplied-key',
        'base_url' => null,
    ]);

    app(GenerateDeckFromPlan::class)->execute(
        new GenerateDeckFromPlanCommand(
            presentation_id: $draftId,
            name: '',
            ai_credential_id: $credential->id,
        ),
    );

    // The resolver overwrote the anthropic provider config with the user's key.
    expect(config('ai.providers.anthropic.key'))->toBe('user-supplied-key');

    Deckster::assertPrompted('# My plan');
});

test('an openai-compatible credential sets the endpoint url', function () {
    Deckster::fake([credFakeDeck()]);

    $team = Team::factory()->create();
    $user = User::factory()->create();
    $draftId = credDraft($team);

    $credential = UserAiCredential::factory()->for($user)->create([
        'driver' => 'openai-compatible',
        'model' => 'fugu',
        'api_key' => '',
        'base_url' => 'https://api.sakana.ai/v1',
    ]);

    app(GenerateDeckFromPlan::class)->execute(
        new GenerateDeckFromPlanCommand(
            presentation_id: $draftId,
            name: '',
            ai_credential_id: $credential->id,
        ),
    );

    expect(config('ai.providers.openai-compatible.url'))->toBe('https://api.sakana.ai/v1')
        ->and(config('ai.providers.openai-compatible'))->not->toHaveKey('key');
});

test('a missing credential falls back to the house provider', function () {
    Deckster::fake([credFakeDeck()]);

    $team = Team::factory()->create();
    $draftId = credDraft($team);

    $entity = app(GenerateDeckFromPlan::class)->execute(
        new GenerateDeckFromPlanCommand(
            presentation_id: $draftId,
            name: '',
            ai_credential_id: 999999, // never existed / deleted
        ),
    );

    expect($entity->draftCompletedAt)->not->toBeNull();

    Deckster::assertPrompted('# My plan');
});
