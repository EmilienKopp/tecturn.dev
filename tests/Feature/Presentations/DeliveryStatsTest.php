<?php

declare(strict_types=1);

use App\Infrastructure\ReadModels\DeliveryStatsReadModel;
use App\Models\Presentation;
use App\Models\PresentationSession;
use App\Models\Rehearsal;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

test('a deck with no history reports empty delivery stats', function () {
    $presentation = Presentation::factory()->create();

    $stats = app(DeliveryStatsReadModel::class)->forPresentation($presentation->id);

    expect($stats->rehearsalCount)->toBe(0)
        ->and($stats->sessionCount)->toBe(0)
        ->and($stats->totalSpokenSeconds)->toBe(0)
        ->and($stats->avgRunSeconds)->toBeNull()
        ->and($stats->avgSecondsPerSlide)->toBeNull();
});

test('delivery stats aggregate rehearsals and finished live sessions', function () {
    $presentation = Presentation::factory()->create();

    // Two rehearsals: 300s over 3 timed slides, 600s over 2 timed slides.
    // The first stored a word count; the second predates the column.
    Rehearsal::factory()
        ->for($presentation, 'presentation')
        ->withSlideTimings([
            ['slide' => 0, 'seconds' => 100],
            ['slide' => 1, 'seconds' => 100],
            ['slide' => 2, 'seconds' => 100],
        ])
        ->create(['team_id' => $presentation->team_id, 'word_count' => 600]);

    Rehearsal::factory()
        ->for($presentation, 'presentation')
        ->withSlideTimings([
            ['slide' => 0, 'seconds' => 300],
            ['slide' => 1, 'seconds' => 300],
        ])
        ->create(['team_id' => $presentation->team_id]);

    // One finished live session of 900s, one still running (ignored).
    PresentationSession::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $presentation->team_id,
        'started_at' => Carbon::parse('2026-09-30 10:00:00'),
        'ended_at' => Carbon::parse('2026-09-30 10:15:00'),
        'word_count' => 1200,
    ]);
    PresentationSession::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $presentation->team_id,
        'ended_at' => null,
    ]);

    $stats = app(DeliveryStatsReadModel::class)->forPresentation($presentation->id);

    expect($stats->rehearsalCount)->toBe(2)
        ->and($stats->sessionCount)->toBe(1)
        // 300 + 600 rehearsed + 900 live.
        ->and($stats->totalSpokenSeconds)->toBe(1800)
        // 1800s across 3 measured runs.
        ->and($stats->avgRunSeconds)->toBe(600)
        // 900 timed seconds across 5 timing entries.
        ->and($stats->avgSecondsPerSlide)->toBe(180)
        // 1800 counted words over 1200 counted seconds (the word-less
        // rehearsal is excluded from both sides of the pace).
        ->and($stats->avgWordsPerMinute)->toBe(90);
});

test('the editor shares the delivery stats with the toolbar', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);

    Rehearsal::factory()
        ->for($presentation, 'presentation')
        ->withSlideTimings([['slide' => 0, 'seconds' => 120]])
        ->create(['team_id' => $presentation->team_id]);

    $this->actingAs($user)->get(route('presentations.edit', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]))->assertInertia(fn (Assert $page) => $page
        ->component('presentations/Editor')
        ->where('deliveryStats.rehearsalCount', 1)
        ->where('deliveryStats.totalSpokenSeconds', 120)
        ->where('deliveryStats.avgSecondsPerSlide', 120));
});

test('team-wide stats average across every deck', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $first = Presentation::factory()->create(['team_id' => $team->id]);
    $second = Presentation::factory()->create(['team_id' => $team->id]);

    Rehearsal::factory()
        ->for($first, 'presentation')
        ->withSlideTimings([['slide' => 0, 'seconds' => 200]])
        ->create(['team_id' => $team->id]);

    Rehearsal::factory()
        ->for($second, 'presentation')
        ->withSlideTimings([['slide' => 0, 'seconds' => 400]])
        ->create(['team_id' => $team->id]);

    PresentationSession::factory()->create([
        'presentation_id' => $first->id,
        'team_id' => $team->id,
        'started_at' => Carbon::parse('2026-09-30 10:00:00'),
        'ended_at' => Carbon::parse('2026-09-30 10:10:00'),
    ]);

    $stats = app(DeliveryStatsReadModel::class)->forTeam($team->id);

    expect($stats->rehearsalCount)->toBe(2)
        ->and($stats->sessionCount)->toBe(1)
        // 200 + 400 rehearsed + 600 live.
        ->and($stats->totalSpokenSeconds)->toBe(1200)
        ->and($stats->avgRunSeconds)->toBe(400)
        // 600 timed seconds across 2 entries.
        ->and($stats->avgSecondsPerSlide)->toBe(300);
});

test('the dashboard shares team speaking stats', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);

    Rehearsal::factory()
        ->for($presentation, 'presentation')
        ->withSlideTimings([['slide' => 0, 'seconds' => 60]])
        ->create(['team_id' => $presentation->team_id]);

    $this->actingAs($user)->get(route('dashboard', [
        'current_team' => $user->currentTeam->slug,
    ]))->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('speakingStats.rehearsalCount', 1)
        ->where('speakingStats.totalSpokenSeconds', 60));
});

test('the start-session beacon stores the deck word count', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user)->post(route('presentations.session.start', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]), ['word_count' => 450])->assertNoContent();

    $this->assertDatabaseHas('presentation_sessions', [
        'presentation_id' => $presentation->id,
        'word_count' => 450,
    ]);
});

test('recording a rehearsal stores the deck word count', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user)->post(route('presentations.rehearsal.store', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]), [
        'started_at' => '2026-09-30T10:00:00Z',
        'ended_at' => '2026-09-30T10:05:00Z',
        'duration_seconds' => 300,
        'word_count' => 750,
        'slide_timings' => [['slide' => 0, 'seconds' => 300]],
    ])->assertRedirect();

    $this->assertDatabaseHas('practice_runs', [
        'presentation_id' => $presentation->id,
        'word_count' => 750,
    ]);
});
