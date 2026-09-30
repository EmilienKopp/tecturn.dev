<?php

declare(strict_types=1);

use App\Models\Presentation;
use App\Models\PresentationSession;
use App\Models\Rehearsal;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

test('closing a session stores the live per-slide timings', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);
    PresentationSession::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $presentation->team_id,
        'ended_at' => null,
    ]);

    $this->actingAs($user)->post(route('presentations.session.end', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]), [
        'slide_timings' => json_encode([
            ['slide' => 0, 'seconds' => 90],
            ['slide' => 1, 'seconds' => 45],
        ]),
    ])->assertNoContent();

    $session = PresentationSession::where('presentation_id', $presentation->id)->sole();

    expect($session->ended_at)->not->toBeNull()
        ->and($session->slide_timings)->toBe([
            ['slide' => 0, 'seconds' => 90],
            ['slide' => 1, 'seconds' => 45],
        ]);
});

test('malformed slide timings are dropped, not stored', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);
    PresentationSession::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $presentation->team_id,
        'ended_at' => null,
    ]);

    $this->actingAs($user)->post(route('presentations.session.end', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]), ['slide_timings' => 'not-json'])->assertNoContent();

    $session = PresentationSession::where('presentation_id', $presentation->id)->sole();

    expect($session->ended_at)->not->toBeNull()
        ->and($session->slide_timings)->toBeNull();
});

test('the session page compares live timings against the rehearsal average', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);

    // Two rehearsals put slide 0 at an average of 60s.
    Rehearsal::factory()
        ->for($presentation, 'presentation')
        ->withSlideTimings([['slide' => 0, 'seconds' => 50]])
        ->create(['team_id' => $presentation->team_id]);
    Rehearsal::factory()
        ->for($presentation, 'presentation')
        ->withSlideTimings([['slide' => 0, 'seconds' => 70]])
        ->create(['team_id' => $presentation->team_id]);

    $session = PresentationSession::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $presentation->team_id,
        'started_at' => Carbon::parse('2026-09-30 10:00:00'),
        'ended_at' => Carbon::parse('2026-09-30 10:10:00'),
        'viewer_count' => 12,
        'reaction_total' => 7,
        'slide_timings' => [['slide' => 0, 'seconds' => 80]],
    ]);

    $this->actingAs($user)->get(route('sessions.show', [
        'current_team' => $user->currentTeam->slug,
        'session' => $session->id,
    ]))->assertInertia(fn (Assert $page) => $page
        ->component('sessions/Show')
        ->where('session.presentation_name', $presentation->name)
        ->where('session.duration_seconds', 600)
        ->where('session.viewer_count', 12)
        ->where('session.slide_timings.0.seconds', 80)
        ->where('rehearsedSeconds.0', 60)
        ->where('rehearsalCount', 2));
});

test('a session of another team is not viewable', function () {
    $user = User::factory()->create();
    $foreign = Presentation::factory()->create();
    $session = PresentationSession::factory()->create([
        'presentation_id' => $foreign->id,
        'team_id' => $foreign->team_id,
        'ended_at' => now(),
    ]);

    $this->actingAs($user)->get(route('sessions.show', [
        'current_team' => $user->currentTeam->slug,
        'session' => $session->id,
    ]))->assertNotFound();
});

test('closing a session stores the per-slide reaction tallies', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);
    PresentationSession::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $presentation->team_id,
        'ended_at' => null,
    ]);

    $this->actingAs($user)->post(route('presentations.session.end', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]), [
        'reaction_slides' => json_encode([
            ['slide' => 0, 'reactions' => ['🔥' => 3]],
            ['slide' => 2, 'reactions' => ['👏' => 1, '❤️' => 2]],
        ]),
    ])->assertNoContent();

    $session = PresentationSession::where('presentation_id', $presentation->id)->sole();

    expect($session->reaction_slides)->toBe([
        ['slide' => 0, 'reactions' => ['🔥' => 3]],
        ['slide' => 2, 'reactions' => ['👏' => 1, '❤️' => 2]],
    ]);
});

test('the session page exposes the per-slide reactions', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);
    $session = PresentationSession::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $presentation->team_id,
        'started_at' => Carbon::parse('2026-09-30 10:00:00'),
        'ended_at' => Carbon::parse('2026-09-30 10:10:00'),
        'slide_timings' => [['slide' => 0, 'seconds' => 80]],
        'reaction_slides' => [['slide' => 0, 'reactions' => ['🔥' => 4]]],
    ]);

    $this->actingAs($user)->get(route('sessions.show', [
        'current_team' => $user->currentTeam->slug,
        'session' => $session->id,
    ]))->assertInertia(fn (Assert $page) => $page
        ->component('sessions/Show')
        ->where('session.reaction_slides.0.slide', 0)
        ->where('session.reaction_slides.0.reactions.🔥', 4));
});
