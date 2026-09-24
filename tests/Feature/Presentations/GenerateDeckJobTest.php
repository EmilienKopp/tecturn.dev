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
use App\Presentation\GeneratingDeckTally;
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

function runDeckJob(User $user, string $plan = '# My plan', string $name = ''): void
{
    $job = new GenerateDeckJob(
        new GenerateDeckFromPlanCommand(
            team_id: $user->currentTeam->id,
            name: $name,
            plan: $plan,
        ),
        $user->id,
        $user->currentTeam->slug,
    );

    $job->handle(app(GenerateDeckFromPlan::class), app(GeneratingDeckTally::class));
}

test('the job builds the deck and broadcasts a ready event to the user', function () {
    Deckster::fake([fakeJobDeck()]);
    Event::fake([DeckGenerated::class]);

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $tally = app(GeneratingDeckTally::class);
    $tally->increment($team->id);

    runDeckJob($user);

    $presentation = Presentation::query()->firstOrFail();
    expect($presentation->team_id)->toBe($team->id)
        ->and($presentation->name)->toBe('Intro to Widgets')
        // The skeleton is cleared once the deck exists.
        ->and($tally->count($team->id))->toBe(0);

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

    runDeckJob($user, name: 'Custom name');

    expect(Presentation::query()->firstOrFail()->name)->toBe('Custom name');
});

test('a failing build broadcasts a failure event and persists nothing', function () {
    Deckster::fake([function () {
        throw new RuntimeException('provider exploded');
    }]);
    Event::fake([DeckGenerationFailed::class]);

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $tally = app(GeneratingDeckTally::class);
    $tally->increment($team->id);

    $job = new GenerateDeckJob(
        new GenerateDeckFromPlanCommand(team_id: $team->id, name: '', plan: '# plan'),
        $user->id,
        $team->slug,
    );

    // The queue calls handle(); on a thrown exception it then calls failed().
    try {
        $job->handle(app(GenerateDeckFromPlan::class), $tally);
    } catch (RuntimeException) {
        $job->failed(new RuntimeException('provider exploded'));
    }

    expect(Presentation::query()->count())->toBe(0)
        // The skeleton is cleared even when the build fails.
        ->and($tally->count($team->id))->toBe(0);

    Event::assertDispatched(DeckGenerationFailed::class, function (DeckGenerationFailed $event) use ($user): bool {
        return $event->broadcastOn()[0]->name === "private-App.Models.User.{$user->id}"
            && $event->broadcastAs() === 'deck.generation.failed';
    });
});
