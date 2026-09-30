<?php

declare(strict_types=1);

use App\Models\Presentation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('every presentation gets a remote token on creation', function () {
    $presentation = Presentation::factory()->create();

    expect($presentation->remote_token)->toBeString()->toHaveLength(32)
        ->and($presentation->remote_token)->not->toBe($presentation->embed_token);
});

test('the editor hands the toolbar a remote pairing url', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user)->get(route('presentations.edit', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]))->assertInertia(fn (Assert $page) => $page
        ->component('presentations/Editor')
        ->where('remoteUrl', route('presentations.remote', ['presentation' => $presentation->remote_token])));
});

test('the present page hands the presenter the remote token', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user)->get(route('presentations.present', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]))->assertInertia(fn (Assert $page) => $page
        ->component('presentations/Present')
        ->where('remote.token', $presentation->remote_token));
});

test('the remote page opens with the deck token, live session or not', function () {
    $presentation = Presentation::factory()->create();

    $this->get(route('presentations.remote', ['presentation' => $presentation->remote_token]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('presentations/Remote')
            ->where('presentationName', $presentation->name)
            ->where('embedToken', $presentation->embed_token)
            ->where('remoteToken', $presentation->remote_token));
});

test('the remote page rejects an unknown token', function () {
    $this->get(route('presentations.remote', ['presentation' => 'no-such-token']))
        ->assertNotFound();
});

test('the embed token is not accepted as a remote token', function () {
    $presentation = Presentation::factory()->create();

    $this->get(route('presentations.remote', ['presentation' => $presentation->embed_token]))
        ->assertNotFound();
});

test('the control channel authorizes a holder of the remote token', function () {
    // The null test broadcaster skips channel auth; exercise the real signer
    // and re-register the channels against it.
    config(['broadcasting.default' => 'reverb']);
    require base_path('routes/channels.php');

    $presentation = Presentation::factory()->create();

    $this->postJson('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-presentation-control.'.$presentation->embed_token,
        'viewer_id' => 'remote-1',
        'remote_token' => $presentation->remote_token,
    ])
        ->assertOk()
        ->assertJsonStructure(['auth']);
});

test('the control channel rejects a wrong or missing remote token', function () {
    config(['broadcasting.default' => 'reverb']);
    require base_path('routes/channels.php');

    $presentation = Presentation::factory()->create();

    // The (public) embed token alone must never grant deck control.
    $this->postJson('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-presentation-control.'.$presentation->embed_token,
        'viewer_id' => 'audience-1',
    ])->assertForbidden();

    $this->postJson('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-presentation-control.'.$presentation->embed_token,
        'viewer_id' => 'audience-1',
        'remote_token' => $presentation->embed_token,
    ])->assertForbidden();

    $this->postJson('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-presentation-control.'.$presentation->embed_token,
        'viewer_id' => 'audience-1',
        'remote_token' => 'guessed-token',
    ])->assertForbidden();
});

test('a remote token only opens the control channel of its own deck', function () {
    config(['broadcasting.default' => 'reverb']);
    require base_path('routes/channels.php');

    $mine = Presentation::factory()->create();
    $other = Presentation::factory()->create();

    $this->postJson('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-presentation-control.'.$other->embed_token,
        'viewer_id' => 'remote-1',
        'remote_token' => $mine->remote_token,
    ])->assertForbidden();
});
