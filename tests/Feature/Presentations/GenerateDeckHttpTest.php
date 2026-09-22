<?php

declare(strict_types=1);

use App\Application\Commands\GenerateDeckFromPlanCommand;
use App\Jobs\GenerateDeckJob;
use App\Models\PresentationModel;
use App\Models\User;
use App\Presentation\GeneratingDeckTally;
use Illuminate\Support\Facades\Queue;

test('generating a draft defers a job and redirects back without building synchronously', function () {
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

    Queue::assertPushed(GenerateDeckJob::class, function (GenerateDeckJob $job) use ($team, $user): bool {
        return $job->command instanceof GenerateDeckFromPlanCommand
            && $job->command->plan === '# My talk plan'
            && $job->command->name === 'Custom name'
            && $job->command->team_id === $team->id
            && $job->userId === $user->id
            && $job->teamSlug === $team->slug;
    });

    // Nothing is built during the request; the job does that later.
    expect(PresentationModel::query()->count())->toBe(0)
        // A skeleton is reserved on the index straight away.
        ->and(app(GeneratingDeckTally::class)->count($team->id))->toBe(1);
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
