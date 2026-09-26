<?php

declare(strict_types=1);

use App\Application\Commands\GenerateDeckFromPlanCommand;
use App\Infrastructure\ReadModels\PresentationReadModel;
use App\Jobs\GenerateDeckJob;
use App\Models\Presentation;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('the read model reports a draft status for each deck', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $ready = Presentation::factory()->create(['team_id' => $team->id]);
    $generating = Presentation::factory()->generatingDraft()->create(['team_id' => $team->id]);
    $failed = Presentation::factory()->failedDraft('boom')->create(['team_id' => $team->id]);

    $rows = collect(app(PresentationReadModel::class)->listForTeam($team->id))
        ->keyBy('id');

    expect($rows[$ready->id]['status'])->toBe('ready')
        ->and($rows[$ready->id]['draft_error'])->toBeNull()
        ->and($rows[$generating->id]['status'])->toBe('generating')
        ->and($rows[$failed->id]['status'])->toBe('failed')
        ->and($rows[$failed->id]['draft_error'])->toBe('boom');
});

test('retrying a failed draft resets it and re-dispatches the build', function () {
    Queue::fake();

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $draft = Presentation::factory()
        ->failedDraft('boom', '# retry me')
        ->create(['team_id' => $team->id, 'name' => 'My deck']);

    $this
        ->actingAs($user)
        ->post(route('presentations.retryDraft', [
            'current_team' => $team->slug,
            'presentation' => $draft->id,
        ]))
        ->assertRedirect();

    $fresh = $draft->fresh();
    expect($fresh->draft_failed_at)->toBeNull()
        ->and($fresh->draft_error)->toBeNull()
        ->and($fresh->draft_completed_at)->toBeNull()
        ->and($fresh->draft_requested_at)->not->toBeNull();

    Queue::assertPushed(GenerateDeckJob::class, function (GenerateDeckJob $job) use ($draft): bool {
        return $job->command instanceof GenerateDeckFromPlanCommand
            && $job->command->presentation_id === $draft->id
            // A user-chosen name is preserved across the retry.
            && $job->command->name === 'My deck';
    });
});

test('a member of another team cannot retry a draft', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $draft = Presentation::factory()
        ->failedDraft()
        ->create(['team_id' => $owner->currentTeam->id]);

    $outsider = User::factory()->create();

    $this
        ->actingAs($outsider)
        ->post(route('presentations.retryDraft', [
            'current_team' => $outsider->currentTeam->slug,
            'presentation' => $draft->id,
        ]))
        ->assertNotFound();

    Queue::assertNothingPushed();
});
