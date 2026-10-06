<?php

declare(strict_types=1);

use App\Models\Presentation;
use App\Models\Rehearsal;
use App\Models\Talk;
use App\Models\User;

test('a newly created deck starts its own talk as 1.0', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(
        route('presentations.store', $user->currentTeam->slug),
        ['name' => 'Fresh deck'],
    );

    $deck = Presentation::query()->sole();
    $talk = Talk::query()->sole();

    expect($talk->title)->toBe('Fresh deck')
        ->and($deck->talk_id)->toBe($talk->id)
        ->and($deck->version_major)->toBe(1)
        ->and($deck->version_minor)->toBe(0);
});

test('the first version bump creates the talk and stamps the source deck as 1.0', function () {
    $user = User::factory()->create();
    $deck = Presentation::factory()->withSlides(2)->create([
        'team_id' => $user->currentTeam->id,
        'name' => 'My keynote',
    ]);

    $response = $this->actingAs($user)->post(
        route('presentations.versions.store', [
            'current_team' => $user->currentTeam->slug,
            'presentation' => $deck->id,
        ]),
        ['bump' => 'major'],
    );

    $deck->refresh();
    $talk = Talk::query()->sole();
    $copy = Presentation::query()
        ->where('talk_id', $talk->id)
        ->whereKeyNot($deck->id)
        ->sole();

    expect($talk->title)->toBe('My keynote')
        ->and($talk->team_id)->toBe($user->currentTeam->id)
        ->and($deck->talk_id)->toBe($talk->id)
        ->and($deck->version_major)->toBe(1)
        ->and($deck->version_minor)->toBe(0)
        ->and($copy->version_major)->toBe(2)
        ->and($copy->version_minor)->toBe(0)
        ->and($copy->content)->toBe($deck->content);

    $response->assertRedirect(route('presentations.edit', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $copy->id,
    ]));
});

test('a minor bump stays within the current major', function () {
    $user = User::factory()->create();
    $talk = Talk::factory()->create(['team_id' => $user->currentTeam->id]);
    $deck = Presentation::factory()->create([
        'team_id' => $user->currentTeam->id,
        'talk_id' => $talk->id,
        'version_major' => 2,
        'version_minor' => 0,
    ]);

    $this->actingAs($user)->post(
        route('presentations.versions.store', [
            'current_team' => $user->currentTeam->slug,
            'presentation' => $deck->id,
        ]),
        ['bump' => 'minor'],
    );

    $this->assertDatabaseHas('presentations', [
        'talk_id' => $talk->id,
        'version_major' => 2,
        'version_minor' => 1,
    ]);
});

test('a major bump picks the next free major across the whole talk', function () {
    $user = User::factory()->create();
    $talk = Talk::factory()->create(['team_id' => $user->currentTeam->id]);
    $old = Presentation::factory()->create([
        'team_id' => $user->currentTeam->id,
        'talk_id' => $talk->id,
        'version_major' => 1,
        'version_minor' => 2,
    ]);
    Presentation::factory()->create([
        'team_id' => $user->currentTeam->id,
        'talk_id' => $talk->id,
        'version_major' => 3,
        'version_minor' => 0,
    ]);

    // Bumping from the old 1.2 deck still lands on 4.0, not a colliding 2.0.
    $this->actingAs($user)->post(
        route('presentations.versions.store', [
            'current_team' => $user->currentTeam->slug,
            'presentation' => $old->id,
        ]),
        ['bump' => 'major'],
    );

    $this->assertDatabaseHas('presentations', [
        'talk_id' => $talk->id,
        'version_major' => 4,
        'version_minor' => 0,
    ]);
});

test('the bump kind is validated', function () {
    $user = User::factory()->create();
    $deck = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user)->post(
        route('presentations.versions.store', [
            'current_team' => $user->currentTeam->slug,
            'presentation' => $deck->id,
        ]),
        ['bump' => 'patch'],
    )->assertSessionHasErrors('bump');
});

test('a rehearsal records the deck version at the moment of the run', function () {
    $user = User::factory()->create();
    $talk = Talk::factory()->create(['team_id' => $user->currentTeam->id]);
    $deck = Presentation::factory()->withSlides(1)->create([
        'team_id' => $user->currentTeam->id,
        'talk_id' => $talk->id,
        'version_major' => 2,
        'version_minor' => 1,
    ]);

    $this->actingAs($user)->post(
        route('presentations.rehearsal.store', [
            'current_team' => $user->currentTeam->slug,
            'presentation' => $deck->id,
        ]),
        [
            'started_at' => now()->subMinutes(10)->toISOString(),
            'ended_at' => now()->toISOString(),
            'duration_seconds' => 600,
            'slide_timings' => [['slide' => 0, 'seconds' => 600]],
        ],
    );

    $this->assertDatabaseHas('practice_runs', [
        'presentation_id' => $deck->id,
        'version_major' => 2,
        'version_minor' => 1,
    ]);
});

test('a deck created from a snapshot becomes the next major with the frozen content', function () {
    $user = User::factory()->create();
    $talk = Talk::factory()->create(['team_id' => $user->currentTeam->id]);
    $deck = Presentation::factory()->withSlides(3)->create([
        'team_id' => $user->currentTeam->id,
        'talk_id' => $talk->id,
        'version_major' => 2,
        'version_minor' => 0,
    ]);
    $frozen = Presentation::factory()->withSlides(1)->make()->content;
    $run = Rehearsal::factory()->create([
        'presentation_id' => $deck->id,
        'team_id' => $user->currentTeam->id,
        'content' => $frozen,
        'version_major' => 2,
        'version_minor' => 0,
    ]);

    $response = $this->actingAs($user)->post(route('rehearsals.deck.store', [
        'current_team' => $user->currentTeam->slug,
        'rehearsal' => $run->id,
    ]));

    $copy = Presentation::query()
        ->where('talk_id', $talk->id)
        ->whereKeyNot($deck->id)
        ->sole();

    expect($copy->version_major)->toBe(3)
        ->and($copy->version_minor)->toBe(0)
        ->and($copy->content)->toBe($frozen);

    $response->assertRedirect(route('presentations.edit', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $copy->id,
    ]));
});

test('a snapshot from an unversioned deck creates the talk and becomes 2.0', function () {
    $user = User::factory()->create();
    $deck = Presentation::factory()->withSlides(1)->create([
        'team_id' => $user->currentTeam->id,
        'name' => 'Lightning talk',
    ]);
    $run = Rehearsal::factory()->create([
        'presentation_id' => $deck->id,
        'team_id' => $user->currentTeam->id,
    ]);

    $this->actingAs($user)->post(route('rehearsals.deck.store', [
        'current_team' => $user->currentTeam->slug,
        'rehearsal' => $run->id,
    ]));

    $deck->refresh();
    $talk = Talk::query()->sole();

    expect($talk->title)->toBe('Lightning talk')
        ->and($deck->version_major)->toBe(1)
        ->and($deck->version_minor)->toBe(0);

    $this->assertDatabaseHas('presentations', [
        'talk_id' => $talk->id,
        'version_major' => 2,
        'version_minor' => 0,
    ]);
});
