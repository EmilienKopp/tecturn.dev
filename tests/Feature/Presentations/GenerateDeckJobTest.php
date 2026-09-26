<?php

declare(strict_types=1);

use App\Ai\Agents\Deckster;
use App\Application\Actions\Presentations\GenerateDeckFromPlan;
use App\Application\Commands\GenerateDeckFromPlanCommand;
use App\Application\Events\DeckGenerated;
use App\Application\Events\DeckGenerationFailed;
use App\Jobs\GenerateDeckJob;
use App\Models\Presentation;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    // Skip the anti-flicker buffer so the suite stays fast.
    config()->set('deck.buffer_seconds', 0);
});

function fakeJobDeck(): array
{
    return [
        'title' => 'Intro to Widgets',
        'slides' => [
            [
                'layout' => 'center',
                'title' => 'Title',
                'blocks' => [
                    ['slot' => 'main', 'type' => 'text', 'content' => 'Intro to Widgets'],
                ],
            ],
        ],
    ];
}

function draftFor(User $user, string $plan = '# My plan', string $name = 'Generating deck…'): Presentation
{
    return Presentation::factory()
        ->generatingDraft($plan)
        ->create(['team_id' => $user->currentTeam->id, 'name' => $name]);
}

function runDeckJob(Presentation $draft, User $user, string $name = ''): void
{
    $job = new GenerateDeckJob(
        new GenerateDeckFromPlanCommand(presentation_id: $draft->id, name: $name),
        $user->id,
        $user->currentTeam->slug,
    );

    $job->handle(app(GenerateDeckFromPlan::class));
}

test('the job fills the draft and broadcasts a ready event to the user', function () {
    Deckster::fake([fakeJobDeck()]);
    Event::fake([DeckGenerated::class]);

    $user = User::factory()->create();
    $draft = draftFor($user);

    runDeckJob($draft, $user);

    $presentation = $draft->fresh();
    expect($presentation->name)->toBe('Intro to Widgets')
        ->and($presentation->draft_completed_at)->not->toBeNull()
        ->and($presentation->draft_failed_at)->toBeNull()
        ->and($presentation->content['slides'])->toHaveCount(1);

    Deckster::assertPrompted('# My plan');

    Event::assertDispatched(DeckGenerated::class, function (DeckGenerated $event) use ($presentation, $user): bool {
        $payload = $event->broadcastWith();

        return $event->broadcastOn()[0]->name === "private-App.Models.User.{$user->id}"
            && $event->broadcastAs() === 'deck.generated'
            && $payload['id'] === $presentation->id
            && $payload['name'] === 'Intro to Widgets'
            && str_contains($payload['url'], (string) $presentation->id);
    });
});

test('an explicit name overrides the generated title', function () {
    Deckster::fake([fakeJobDeck()]);
    Event::fake([DeckGenerated::class]);

    $user = User::factory()->create();
    $draft = draftFor($user, name: 'Custom name');

    runDeckJob($draft, $user, name: 'Custom name');

    expect($draft->fresh()->name)->toBe('Custom name');
});

test('a failing build marks the draft failed and broadcasts a failure event', function () {
    Deckster::fake([function () {
        throw new RuntimeException('provider exploded');
    }]);
    Event::fake([DeckGenerationFailed::class]);

    $user = User::factory()->create();
    $draft = draftFor($user);

    $job = new GenerateDeckJob(
        new GenerateDeckFromPlanCommand(presentation_id: $draft->id, name: ''),
        $user->id,
        $user->currentTeam->slug,
    );

    // The queue calls handle(); on a thrown exception it then calls failed().
    try {
        $job->handle(app(GenerateDeckFromPlan::class));
    } catch (RuntimeException) {
        $job->failed(new RuntimeException('provider exploded'));
    }

    $presentation = $draft->fresh();
    // The row survives so the user sees the failure and can retry.
    expect($presentation)->not->toBeNull()
        ->and($presentation->draft_failed_at)->not->toBeNull()
        ->and($presentation->draft_error)->toBe('provider exploded')
        ->and($presentation->draft_completed_at)->toBeNull();

    Event::assertDispatched(DeckGenerationFailed::class, function (DeckGenerationFailed $event) use ($user): bool {
        return $event->broadcastOn()[0]->name === "private-App.Models.User.{$user->id}"
            && $event->broadcastAs() === 'deck.generation.failed';
    });
});
