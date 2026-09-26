<?php

declare(strict_types=1);

use App\Jobs\GenerateDeckJob;
use App\Models\Presentation;
use App\Models\User;
use App\Models\UserAiCredential;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

function generateDeck(User $user, ?int $credentialId = null): TestResponse
{
    return test()
        ->actingAs($user)
        ->post(route('presentations.generate', ['current_team' => $user->currentTeam->slug]), [
            'plan' => '# My talk plan',
            'ai_credential_id' => $credentialId,
        ]);
}

test('the house model is limited to the configured number of builds per user', function () {
    Queue::fake();
    config(['deckster.house_limit.max' => 3]);

    $user = User::factory()->create();

    for ($i = 0; $i < 3; $i++) {
        generateDeck($user)->assertRedirect()->assertSessionHasNoErrors();
    }

    // The fourth is blocked: no new draft row, no job queued.
    generateDeck($user)->assertRedirect();

    expect(Presentation::query()->count())->toBe(3);
    Queue::assertPushed(GenerateDeckJob::class, 3);
});

test('own-key builds are not rate limited', function () {
    Queue::fake();
    config(['deckster.house_limit.max' => 1]);

    $user = User::factory()->create();
    $credential = UserAiCredential::factory()->for($user)->default()->create();

    for ($i = 0; $i < 5; $i++) {
        generateDeck($user, $credential->id)->assertRedirect();
    }

    expect(Presentation::query()->count())->toBe(5);
    Queue::assertPushed(GenerateDeckJob::class, 5);
});

test('the chosen credential is passed to the build job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $credential = UserAiCredential::factory()->for($user)->create();

    generateDeck($user, $credential->id)->assertRedirect();

    Queue::assertPushed(GenerateDeckJob::class, function (GenerateDeckJob $job) use ($credential): bool {
        return $job->command->ai_credential_id === $credential->id;
    });
});

test('a user cannot build with another users credential', function () {
    Queue::fake();

    $user = User::factory()->create();
    $other = UserAiCredential::factory()->for(User::factory())->create();

    generateDeck($user, $other->id)->assertSessionHasErrors('ai_credential_id');

    Queue::assertNothingPushed();
});

test('the limit is per user, not global', function () {
    Queue::fake();
    config(['deckster.house_limit.max' => 1]);

    $alice = User::factory()->create();
    $bob = User::factory()->create();

    generateDeck($alice)->assertRedirect();
    generateDeck($alice)->assertRedirect(); // blocked
    generateDeck($bob)->assertRedirect(); // fresh budget

    expect(Presentation::query()->count())->toBe(2);
});
