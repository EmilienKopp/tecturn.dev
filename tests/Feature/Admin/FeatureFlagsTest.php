<?php

use App\Enums\RegistrationMode;
use App\Models\Team;
use App\Models\User;
use App\Support\Features;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Pennant\Feature;

beforeEach(function () {
    config()->set('admin.emails', ['boss@example.com']);
});

function admin(): User
{
    return User::factory()->create(['email' => 'boss@example.com']);
}

test('guests are redirected from the features page', function () {
    $this->get(route('admin.features'))->assertRedirect(route('login'));
});

test('non-admins cannot view the features page', function () {
    $user = User::factory()->create(['email' => 'nobody@example.com']);

    $this->actingAs($user)->get(route('admin.features'))->assertForbidden();
});

test('admins see global flags and the team list', function () {
    $admin = admin();

    $response = $this->actingAs($admin)->get(route('admin.features'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/FeatureFlags')
        ->has('globalFlags')
        ->where('globalFlags.0.key', 'registration')
        ->has('teams')
        ->where('selectedTeamId', null)
        ->where('teamFlags', []),
    );
});

test('selecting a team returns that team\'s flag values', function () {
    $admin = admin();
    $team = Team::factory()->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.features', ['team_id' => $team->id]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('selectedTeamId', $team->id)
        ->where('teamFlags.0.key', 'live_translation')
        ->where('teamFlags.0.value', true),
    );
});

test('an admin can change the global registration mode', function () {
    $admin = admin();

    $this->actingAs($admin)
        ->post(route('admin.features.update'), [
            'key' => 'registration',
            'value' => RegistrationMode::Open->value,
        ])
        ->assertRedirect();

    Feature::flushCache();
    expect(Features::registration())->toBe(RegistrationMode::Open);
});

test('an admin can turn a team flag off', function () {
    $admin = admin();
    $team = Team::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.features.update'), [
            'key' => 'live_translation',
            'value' => false,
            'team_id' => $team->id,
        ])
        ->assertRedirect();

    Feature::flushCache();
    expect(Features::teamHas($team, 'live_translation'))->toBeFalse();
});

test('non-admins cannot update flags', function () {
    $user = User::factory()->create(['email' => 'nobody@example.com']);

    $this->actingAs($user)
        ->post(route('admin.features.update'), [
            'key' => 'registration',
            'value' => RegistrationMode::Open->value,
        ])
        ->assertForbidden();
});

test('an invalid select value is rejected', function () {
    $admin = admin();

    $this->actingAs($admin)
        ->post(route('admin.features.update'), [
            'key' => 'registration',
            'value' => 'nonsense',
        ])
        ->assertSessionHasErrors('value');
});

test('a team flag requires a team id', function () {
    $admin = admin();

    $this->actingAs($admin)
        ->post(route('admin.features.update'), [
            'key' => 'live_translation',
            'value' => false,
        ])
        ->assertSessionHasErrors('team_id');
});

test('a global flag rejects a team id', function () {
    $admin = admin();
    $team = Team::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.features.update'), [
            'key' => 'registration',
            'value' => RegistrationMode::Open->value,
            'team_id' => $team->id,
        ])
        ->assertSessionHasErrors('team_id');
});
