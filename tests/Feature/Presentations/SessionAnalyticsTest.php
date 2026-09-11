<?php

declare(strict_types=1);

use App\Models\PresentationModel;
use App\Models\PresentationSessionModel;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('going live opens a session for the deck', function () {
    $user = User::factory()->create();
    $presentation = PresentationModel::factory()->create(['team_id' => $user->currentTeam->id]);

    $response = $this->actingAs($user)->post(route('presentations.session.start', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]));

    $response->assertNoContent();

    $this->assertDatabaseHas('presentation_sessions', [
        'presentation_id' => $presentation->id,
        'team_id' => $user->currentTeam->id,
        'ended_at' => null,
    ]);
});

test('starting is idempotent while a session is already live', function () {
    $user = User::factory()->create();
    $presentation = PresentationModel::factory()->create(['team_id' => $user->currentTeam->id]);

    $url = route('presentations.session.start', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]);

    $this->actingAs($user)->post($url)->assertNoContent();
    $this->actingAs($user)->post($url)->assertNoContent();

    expect(PresentationSessionModel::where('presentation_id', $presentation->id)->count())->toBe(1);
});

test('closing a session records ended_at', function () {
    $user = User::factory()->create();
    $presentation = PresentationModel::factory()->create(['team_id' => $user->currentTeam->id]);
    $session = PresentationSessionModel::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $user->currentTeam->id,
    ]);

    $this->actingAs($user)->post(route('presentations.session.end', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]))->assertNoContent();

    expect($session->refresh()->ended_at)->not->toBeNull();
});

test('batched reactions accumulate on the live session and count the viewer', function () {
    $presentation = PresentationModel::factory()->create();
    $session = PresentationSessionModel::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $presentation->team_id,
    ]);

    $this->postJson(route('presentations.reactions.batch', [
        'presentation' => $presentation->embed_token,
    ]), [
        'viewerId' => 'viewer-1',
        'counts' => ['🔥' => 3, '❤️' => 1],
    ])->assertNoContent();

    $session->refresh();

    expect($session->reaction_counts)->toBe(['🔥' => 3, '❤️' => 1])
        ->and($session->reaction_total)->toBe(4)
        ->and($session->viewer_count)->toBe(1);
});

test('a heartbeat with no reactions still counts the viewer', function () {
    $presentation = PresentationModel::factory()->create();
    $session = PresentationSessionModel::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $presentation->team_id,
    ]);

    $this->postJson(route('presentations.reactions.batch', [
        'presentation' => $presentation->embed_token,
    ]), ['viewerId' => 'viewer-1'])->assertNoContent();

    expect($session->refresh()->viewer_count)->toBe(1);
});

test('an anonymous viewer can authorize on the live presence channel', function () {
    // The live "watching now" count now rides a Reverb presence channel; the
    // null test broadcaster skips channel auth, so exercise the real signer.
    // Channels register against the boot-time default (null), so re-register
    // them on the reverb driver after switching.
    config(['broadcasting.default' => 'reverb']);
    require base_path('routes/channels.php');

    $presentation = PresentationModel::factory()->create();

    $this->postJson('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'presence-presentation-live.'.$presentation->embed_token,
        'viewer_id' => 'viewer-1',
    ])
        ->assertOk()
        ->assertJsonStructure(['auth', 'channel_data']);
});

test('joining the presence channel for an unknown talk is rejected', function () {
    config(['broadcasting.default' => 'reverb']);

    $this->postJson('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'presence-presentation-live.does-not-exist',
        'viewer_id' => 'viewer-1',
    ])->assertForbidden();
});

test('the presence channel rejects a member with no viewer id', function () {
    config(['broadcasting.default' => 'reverb']);

    $presentation = PresentationModel::factory()->create();

    $this->postJson('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'presence-presentation-live.'.$presentation->embed_token,
    ])->assertForbidden();
});

test('reactions are dropped when no session is live', function () {
    $presentation = PresentationModel::factory()->create();

    $this->postJson(route('presentations.reactions.batch', [
        'presentation' => $presentation->embed_token,
    ]), ['viewerId' => 'viewer-1', 'counts' => ['🔥' => 2]])->assertNoContent();

    expect(PresentationSessionModel::count())->toBe(0);
});

test('unsupported reaction emojis are rejected', function () {
    $presentation = PresentationModel::factory()->create();
    PresentationSessionModel::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $presentation->team_id,
    ]);

    $this->postJson(route('presentations.reactions.batch', [
        'presentation' => $presentation->embed_token,
    ]), ['viewerId' => 'viewer-1', 'counts' => ['💩' => 2]])->assertUnprocessable();
});

test('the dashboard exposes session analytics for the team', function () {
    $user = User::factory()->create();
    $presentation = PresentationModel::factory()->create([
        'team_id' => $user->currentTeam->id,
        'name' => 'Scaling Postgres',
    ]);
    PresentationSessionModel::factory()->ended()->withReactions(['🔥' => 8, '👏' => 2])->create([
        'presentation_id' => $presentation->id,
        'team_id' => $user->currentTeam->id,
        'viewer_count' => 15,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('engagement.total_sessions', 1)
        ->where('engagement.total_reactions', 10)
        ->where('engagement.total_viewers', 15)
        ->where('engagement.top_emoji', '🔥')
        ->has('recentSessions', 1)
        ->where('recentSessions.0.presentation_name', 'Scaling Postgres')
        ->where('recentSessions.0.reaction_total', 10)
        ->has('recentDecks', 1),
    );
});
