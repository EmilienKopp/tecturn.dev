<?php

declare(strict_types=1);

use App\Models\Presentation;
use App\Models\PresentationSession;
use App\Models\Talk;
use App\Models\TalkEvent;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected away from the calendar', function () {
    $user = User::factory()->create();

    $this->get(route('calendar.index', $user->currentTeam->slug))
        ->assertRedirect();
});

test('a team member sees the calendar with the team events', function () {
    $user = User::factory()->create();
    $talk = Talk::factory()->create([
        'team_id' => $user->currentTeam->id,
        'title' => 'A finished talk',
    ]);
    Presentation::factory()->create([
        'team_id' => $user->currentTeam->id,
        'talk_id' => $talk->id,
        'version_major' => 1,
        'version_minor' => 0,
    ]);
    $latest = Presentation::factory()->create([
        'team_id' => $user->currentTeam->id,
        'talk_id' => $talk->id,
        'version_major' => 2,
        'version_minor' => 0,
    ]);
    // An AI draft still generating must not steal the "latest deck" slot.
    Presentation::factory()->create([
        'team_id' => $user->currentTeam->id,
        'talk_id' => $talk->id,
        'version_major' => 3,
        'version_minor' => 0,
        'draft_requested_at' => now(),
        'draft_completed_at' => null,
    ]);
    $generatingTalk = Talk::factory()->create([
        'team_id' => $user->currentTeam->id,
        'title' => 'Z still generating',
    ]);
    Presentation::factory()->create([
        'team_id' => $user->currentTeam->id,
        'talk_id' => $generatingTalk->id,
        'draft_requested_at' => now(),
        'draft_completed_at' => null,
    ]);
    TalkEvent::factory()->withStartTime('10:00')->create([
        'team_id' => $user->currentTeam->id,
        'talk_id' => $talk->id,
        'name' => 'PHP Meetup',
        'date' => '2026-11-12',
    ]);
    TalkEvent::factory()->create(); // another team's event

    $this->actingAs($user)
        ->get(route('calendar.index', $user->currentTeam->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar/Index')
            ->count('events', 1)
            ->where('events.0.name', 'PHP Meetup')
            ->where('events.0.date', '2026-11-12')
            ->where('events.0.start_time', '10:00')
            ->where('events.0.talk_title', $talk->title)
            ->count('talks', 2)
            ->where('talks.0.latest_presentation_id', $latest->id)
            ->whereNot('talks.0.generated_at', null)
            ->where('talks.1.id', $generatingTalk->id)
            ->where('talks.1.latest_presentation_id', null)
            ->where('talks.1.generated_at', null)
        );
});

test('a team member can schedule a talk event', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('talk-events.store', $user->currentTeam->slug), [
            'name' => 'Conference keynote',
            'date' => '2026-12-01',
            'start_time' => '09:30',
            'talk_id' => null,
        ])
        ->assertRedirect(route('calendar.index', $user->currentTeam->slug));

    $this->assertDatabaseHas('talk_events', [
        'team_id' => $user->currentTeam->id,
        'name' => 'Conference keynote',
        'date' => '2026-12-01 00:00:00',
        'start_time' => '09:30',
        'talk_id' => null,
    ]);
});

test('the start time is optional', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('talk-events.store', $user->currentTeam->slug), [
            'name' => 'Date blocked, details later',
            'date' => '2026-12-24',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('talk_events', [
        'name' => 'Date blocked, details later',
        'start_time' => null,
    ]);
});

test('an event cannot attach a talk from another team', function () {
    $user = User::factory()->create();
    $foreignTalk = Talk::factory()->create();

    $this->actingAs($user)
        ->from(route('calendar.index', $user->currentTeam->slug))
        ->post(route('talk-events.store', $user->currentTeam->slug), [
            'name' => 'Sneaky event',
            'date' => '2026-12-01',
            'talk_id' => $foreignTalk->id,
        ])
        ->assertSessionHasErrors('talk_id');

    $this->assertDatabaseMissing('talk_events', ['name' => 'Sneaky event']);
});

test('a team member can update an event', function () {
    $user = User::factory()->create();
    $talk = Talk::factory()->create(['team_id' => $user->currentTeam->id]);
    $event = TalkEvent::factory()->create([
        'team_id' => $user->currentTeam->id,
        'name' => 'Old name',
        'date' => '2026-11-12',
    ]);

    $this->actingAs($user)
        ->put(route('talk-events.update', [
            'current_team' => $user->currentTeam->slug,
            'talk_event' => $event->id,
        ]), [
            'name' => 'New name',
            'date' => '2026-11-13',
            'start_time' => '18:00',
            'talk_id' => $talk->id,
        ])
        ->assertRedirect(route('calendar.index', $user->currentTeam->slug));

    $this->assertDatabaseHas('talk_events', [
        'id' => $event->id,
        'name' => 'New name',
        'date' => '2026-11-13 00:00:00',
        'start_time' => '18:00',
        'talk_id' => $talk->id,
    ]);
});

test('another team cannot touch a foreign event', function () {
    $event = TalkEvent::factory()->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->put(route('talk-events.update', [
            'current_team' => $stranger->currentTeam->slug,
            'talk_event' => $event->id,
        ]), [
            'name' => 'Hijacked',
            'date' => '2026-11-13',
        ])
        ->assertNotFound();
});

test('deleting an event detaches its sessions', function () {
    $user = User::factory()->create();
    $event = TalkEvent::factory()->create(['team_id' => $user->currentTeam->id]);
    $session = PresentationSession::factory()->create([
        'team_id' => $user->currentTeam->id,
        'talk_event_id' => $event->id,
    ]);

    $this->actingAs($user)
        ->delete(route('talk-events.destroy', [
            'current_team' => $user->currentTeam->slug,
            'talk_event' => $event->id,
        ]))
        ->assertRedirect(route('calendar.index', $user->currentTeam->slug));

    $this->assertDatabaseMissing('talk_events', ['id' => $event->id]);
    $this->assertDatabaseHas('presentation_sessions', [
        'id' => $session->id,
        'talk_event_id' => null,
    ]);
});
