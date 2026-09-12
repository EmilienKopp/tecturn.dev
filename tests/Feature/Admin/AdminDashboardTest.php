<?php

use App\Models\PresentationModel;
use App\Models\PresentationSessionModel;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('login'));
});

test('authenticated users not on the allowlist are forbidden', function () {
    config()->set('admin.emails', ['boss@example.com']);

    $user = User::factory()->create(['email' => 'nobody@example.com']);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertForbidden();
});

test('allowlisted admins can view the overview', function () {
    config()->set('admin.emails', ['boss@example.com']);

    $user = User::factory()->create(['email' => 'boss@example.com']);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/Dashboard')
        ->has('overview')
        ->where('auth.isAdmin', true),
    );
});

test('the allowlist match is case-insensitive', function () {
    config()->set('admin.emails', ['boss@example.com']);

    $user = User::factory()->create(['email' => 'Boss@Example.com']);

    $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
});

test('non-admins cannot reach the user list', function () {
    config()->set('admin.emails', ['boss@example.com']);

    $user = User::factory()->create(['email' => 'nobody@example.com']);

    $this->actingAs($user)->get(route('admin.users'))->assertForbidden();
});

test('admins see every registered user in the list', function () {
    config()->set('admin.emails', ['boss@example.com']);

    $admin = User::factory()->create(['email' => 'boss@example.com']);
    User::factory()->count(2)->create();

    $response = $this->actingAs($admin)->get(route('admin.users'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/Users')
        ->has('users', User::count()),
    );
});

test('non-admins cannot view a user detail page', function () {
    config()->set('admin.emails', ['boss@example.com']);

    $viewer = User::factory()->create(['email' => 'nobody@example.com']);
    $target = User::factory()->create();

    $this->actingAs($viewer)->get(route('admin.users.show', $target))->assertForbidden();
});

test('admins see a user\'s per-team presentation analytics', function () {
    config()->set('admin.emails', ['boss@example.com']);

    $admin = User::factory()->create(['email' => 'boss@example.com']);

    $target = User::factory()->create();
    $team = $target->currentTeam;

    $presentation = PresentationModel::factory()->create(['team_id' => $team->id]);
    PresentationSessionModel::factory()->create([
        'team_id' => $team->id,
        'presentation_id' => $presentation->id,
        'reaction_total' => 5,
        'viewer_count' => 3,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.users.show', $target));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/User')
        ->where('user.id', $target->id)
        ->has('teams', 1)
        ->where('teams.0.name', $team->name)
        ->where('teams.0.engagement.total_sessions', 1)
        ->where('teams.0.engagement.total_reactions', 5)
        ->has('teams.0.recentSessions', 1),
    );
});

test('the overview reports platform-wide counts', function () {
    config()->set('admin.emails', ['boss@example.com']);

    $admin = User::factory()->create(['email' => 'boss@example.com']);
    $team = Team::factory()->create();
    PresentationModel::factory()->count(3)->create(['team_id' => $team->id]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('overview.total_presentations', 3)
        ->where('overview.total_users', User::count()),
    );
});
