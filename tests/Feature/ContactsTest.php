<?php

use App\Models\PresentationModel;
use App\Models\PresentationSessionModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('contacts can be searched by public and social handles without exposing email addresses', function () {
    $viewer = User::factory()->create(['handle' => 'viewer']);
    $speaker = User::factory()->create([
        'name' => 'Taylor Speaker',
        'handle' => 'taylor',
        'social_x_handle' => 'taylorx',
        'social_github_handle' => 'taylorgit',
        'email' => 'taylor@example.com',
    ]);

    $response = $this
        ->actingAs($viewer)
        ->get(route('contacts.index', ['search' => 'taylorgit']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Contacts')
        ->where('search', 'taylorgit')
        ->has('results', 1)
        ->where('results.0.name', 'Taylor Speaker')
        ->where('results.0.handle', 'taylor')
        ->where('results.0.social_x_handle', 'taylorx')
        ->where('results.0.social_github_handle', 'taylorgit')
        ->missing('results.0.email'),
    );
});

test('discover section excludes people without any public handle', function () {
    $viewer = User::factory()->create(['handle' => 'viewer']);

    User::factory()->create([
        'name' => 'Handleless Harry',
        'handle' => null,
        'social_x_handle' => null,
        'social_github_handle' => null,
    ]);

    User::factory()->create([
        'name' => 'Visible Vera',
        'handle' => null,
        'social_x_handle' => null,
        'social_github_handle' => 'veragit',
    ]);

    $response = $this
        ->actingAs($viewer)
        ->get(route('contacts.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Contacts')
        ->has('results', 1)
        ->where('results.0.name', 'Visible Vera'),
    );
});

test('following someone shows their public talks and stats but hides private talks', function () {
    $viewer = User::factory()->create(['handle' => 'viewer']);
    $speaker = User::factory()->create([
        'name' => 'Taylor Speaker',
        'handle' => 'taylor',
        'social_x_handle' => 'taylorx',
    ]);

    $publicTalk = PresentationModel::factory()->create([
        'team_id' => $speaker->currentTeam->id,
        'name' => 'Public Talk',
        'is_private' => false,
    ]);

    $privateTalk = PresentationModel::factory()->create([
        'team_id' => $speaker->currentTeam->id,
        'name' => 'Private Talk',
        'is_private' => true,
    ]);

    PresentationSessionModel::factory()->ended()->withReactions(['🔥' => 6])->create([
        'presentation_id' => $publicTalk->id,
        'team_id' => $speaker->currentTeam->id,
        'viewer_count' => 18,
    ]);

    PresentationSessionModel::factory()->ended()->withReactions(['👏' => 4])->create([
        'presentation_id' => $privateTalk->id,
        'team_id' => $speaker->currentTeam->id,
        'viewer_count' => 99,
    ]);

    $this
        ->actingAs($viewer)
        ->post(route('contacts.follow.store', $speaker))
        ->assertRedirect();

    $response = $this
        ->actingAs($viewer)
        ->get(route('contacts.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Contacts')
        ->has('following', 1)
        ->where('following.0.name', 'Taylor Speaker')
        ->has('followedTalks', 1)
        ->where('followedTalks.0.name', 'Public Talk')
        ->where('followedTalks.0.viewer_count', 18)
        ->where('followedTalks.0.reaction_total', 6)
        ->where('results.0.talks_count', 1)
        ->where('results.0.total_viewers', 18)
        ->where('results.0.total_reactions', 6),
    );
});

test('contacts can be unfollowed', function () {
    $viewer = User::factory()->create(['handle' => 'viewer']);
    $speaker = User::factory()->create(['handle' => 'speaker']);

    DB::table('user_follows')->insert([
        'follower_user_id' => $viewer->id,
        'followed_user_id' => $speaker->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this
        ->actingAs($viewer)
        ->delete(route('contacts.follow.destroy', $speaker))
        ->assertRedirect();

    $this->assertDatabaseMissing('user_follows', [
        'follower_user_id' => $viewer->id,
        'followed_user_id' => $speaker->id,
    ]);
});
