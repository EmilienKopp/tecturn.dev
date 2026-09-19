<?php

use App\Models\PresentationModel;
use App\Models\PresentationSessionModel;
use App\Models\RehearsalModel;
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

    // Following only counts once the speaker accepts the request.
    $this
        ->actingAs($speaker)
        ->post(route('contacts.follow.accept', $viewer))
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

test('following starts as a pending request that does not grant follower status', function () {
    $viewer = User::factory()->create(['handle' => 'viewer']);
    $speaker = User::factory()->create(['handle' => 'speaker']);

    $this
        ->actingAs($viewer)
        ->post(route('contacts.follow.store', $speaker))
        ->assertRedirect();

    $this->assertDatabaseHas('user_follows', [
        'follower_user_id' => $viewer->id,
        'followed_user_id' => $speaker->id,
        'status' => 'pending',
    ]);

    // The requester sees "pending", not an established follow.
    $this
        ->actingAs($viewer)
        ->get(route('contacts.index', ['search' => 'speaker']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('results.0.follow_status', 'pending')
            ->has('following', 0),
        );

    // The speaker sees the incoming request but no follower yet.
    $this
        ->actingAs($speaker)
        ->get(route('contacts.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('followRequests', 1)
            ->where('followRequests.0.id', $viewer->id)
            ->has('followers', 0),
        );
});

test('accepting a follow request turns it into a follow', function () {
    $viewer = User::factory()->create(['handle' => 'viewer']);
    $speaker = User::factory()->create(['handle' => 'speaker']);

    $this->actingAs($viewer)->post(route('contacts.follow.store', $speaker));

    $this
        ->actingAs($speaker)
        ->post(route('contacts.follow.accept', $viewer))
        ->assertRedirect();

    $this->assertDatabaseHas('user_follows', [
        'follower_user_id' => $viewer->id,
        'followed_user_id' => $speaker->id,
        'status' => 'accepted',
    ]);

    $this
        ->actingAs($speaker)
        ->get(route('contacts.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('followRequests', 0)
            ->has('followers', 1)
            ->where('followers.0.id', $viewer->id),
        );
});

test('rejecting a follow request removes it', function () {
    $viewer = User::factory()->create(['handle' => 'viewer']);
    $speaker = User::factory()->create(['handle' => 'speaker']);

    $this->actingAs($viewer)->post(route('contacts.follow.store', $speaker));

    $this
        ->actingAs($speaker)
        ->delete(route('contacts.follow.reject', $viewer))
        ->assertRedirect();

    $this->assertDatabaseMissing('user_follows', [
        'follower_user_id' => $viewer->id,
        'followed_user_id' => $speaker->id,
    ]);
});

test('re-requesting after acceptance does not downgrade the follow to pending', function () {
    $viewer = User::factory()->create(['handle' => 'viewer']);
    $speaker = User::factory()->create(['handle' => 'speaker']);

    $this->actingAs($viewer)->post(route('contacts.follow.store', $speaker));
    $this->actingAs($speaker)->post(route('contacts.follow.accept', $viewer));
    $this->actingAs($viewer)->post(route('contacts.follow.store', $speaker));

    $this->assertDatabaseHas('user_follows', [
        'follower_user_id' => $viewer->id,
        'followed_user_id' => $speaker->id,
        'status' => 'accepted',
    ]);
});

test('a pending follower cannot be asked for a rehearsal review', function () {
    $requester = User::factory()->create(['handle' => 'requester']);
    $presentation = PresentationModel::factory()->withSlides(2)->create(['team_id' => $requester->currentTeam->id]);
    $run = RehearsalModel::factory()->withStepEvents()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $requester->currentTeam->id,
        'content' => $presentation->content,
    ]);
    $pendingFollower = User::factory()->create(['handle' => 'pending']);

    $this->actingAs($pendingFollower)->post(route('contacts.follow.store', $requester));

    $this->actingAs($requester)->post(route('rehearsals.reviews.store', [
        'current_team' => $requester->currentTeam->slug,
        'rehearsal' => $run->id,
    ]), ['reviewer_user_id' => $pendingFollower->id])->assertSessionHasErrors('reviewer_user_id');
});
