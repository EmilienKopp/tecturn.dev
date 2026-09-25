<?php

declare(strict_types=1);

use App\Application\Commands\GenerateDeckFromPlanCommand;
use App\Jobs\GenerateDeckJob;
use App\Models\PresentationModel;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('generating a draft creates a pending row and defers the build', function () {
    Queue::fake();

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->post(route('presentations.generate', ['current_team' => $team->slug]), [
            'plan' => '# My talk plan',
            'name' => 'Custom name',
        ]);

    $response->assertRedirect();

    // The row exists straight away and is inspectable as an in-progress draft.
    $presentation = PresentationModel::query()->firstOrFail();
    expect($presentation->team_id)->toBe($team->id)
        ->and($presentation->name)->toBe('Custom name')
        ->and($presentation->draft_plan)->toBe('# My talk plan')
        ->and($presentation->draft_requested_at)->not->toBeNull()
        ->and($presentation->draft_completed_at)->toBeNull()
        ->and($presentation->draft_failed_at)->toBeNull();

    Queue::assertPushed(GenerateDeckJob::class, function (GenerateDeckJob $job) use ($team, $user, $presentation): bool {
        return $job->command instanceof GenerateDeckFromPlanCommand
            && $job->command->presentation_id === $presentation->id
            && $job->command->name === 'Custom name'
            && $job->userId === $user->id
            && $job->teamSlug === $team->slug;
    });
});

test('a draft without a name uses a placeholder until the AI titles it', function () {
    Queue::fake();

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this
        ->actingAs($user)
        ->post(route('presentations.generate', ['current_team' => $team->slug]), [
            'plan' => '# My talk plan',
        ])
        ->assertRedirect();

    expect(PresentationModel::query()->firstOrFail()->name)->toBe('Generating deck…');
});

test('the plan is required', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->post(route('presentations.generate', ['current_team' => $team->slug]), [
            'plan' => '',
        ]);

    $response->assertSessionHasErrors('plan');
});

test('guests cannot generate a draft', function () {
    $user = User::factory()->create();

    $response = $this->post(route('presentations.generate', ['current_team' => $user->currentTeam->slug]), [
        'plan' => '# plan',
    ]);

    $response->assertRedirect(route('login'));
});
