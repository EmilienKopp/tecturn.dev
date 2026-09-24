<?php

declare(strict_types=1);

use App\Models\Presentation;
use App\Models\Rehearsal;
use App\Models\User;
use Illuminate\Http\Testing\File as TestingFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A fake upload whose bytes actually sniff as WebM — both the mimetypes rule
 * and the media library detect the type from content, not the client mime.
 */
function fakeWebmUpload(): TestingFile
{
    $bytes = hex2bin('1a45dfa39f4286810142f7810142f2810442f381084282847765626d').str_repeat("\x00", 128);

    return UploadedFile::fake()->createWithContent('rehearsal.webm', $bytes);
}

test('stopping a rehearsal persists the timings with a snapshot of the deck', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->withSlides(3)->create(['team_id' => $user->currentTeam->id]);

    $response = $this->actingAs($user)->post(route('presentations.rehearsal.store', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]), [
        'started_at' => now()->subMinutes(10)->toISOString(),
        'ended_at' => now()->toISOString(),
        'duration_seconds' => 540,
        'slide_timings' => [
            ['slide' => 0, 'seconds' => 200],
            ['slide' => 1, 'seconds' => 220],
            ['slide' => 2, 'seconds' => 120],
        ],
    ]);

    $run = Rehearsal::sole();

    $response->assertRedirect(route('rehearsals.show', [
        'current_team' => $user->currentTeam->slug,
        'rehearsal' => $run->id,
    ]));

    expect($run->presentation_id)->toBe($presentation->id)
        ->and($run->team_id)->toBe($user->currentTeam->id)
        ->and($run->duration_seconds)->toBe(540)
        ->and($run->slide_timings)->toHaveCount(3)
        ->and($run->content)->toBe($presentation->content)
        ->and($run->content['slides'])->toHaveCount(3);
});

test('a rehearsal stores the step-event timeline and the voice recording', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $presentation = Presentation::factory()->withSlides(2)->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user)->post(route('presentations.rehearsal.store', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]), [
        'started_at' => now()->subMinutes(2)->toISOString(),
        'ended_at' => now()->toISOString(),
        // Numbers arrive as strings in real multipart posts (the audio file
        // forces FormData); the backend must cast before storing the JSON.
        'duration_seconds' => '120',
        'slide_timings' => [['slide' => '0', 'seconds' => '70'], ['slide' => '1', 'seconds' => '50']],
        'step_events' => [
            ['at_ms' => '0', 'slide' => '0', 'step' => '0'],
            ['at_ms' => '4000', 'slide' => '0', 'step' => '1'],
            ['at_ms' => '70000', 'slide' => '1', 'step' => '0'],
        ],
        'audio' => fakeWebmUpload(),
    ])->assertRedirect();

    $run = Rehearsal::sole();

    expect($run->step_events)->toHaveCount(3)
        ->and($run->step_events[1])->toBe(['at_ms' => 4000, 'slide' => 0, 'step' => 1])
        ->and($run->slide_timings[0])->toBe(['slide' => 0, 'seconds' => 70])
        ->and($run->duration_seconds)->toBe(120)
        ->and($run->getFirstMedia(Rehearsal::RECORDING_COLLECTION))->not->toBeNull();
});

test('a rehearsal without audio or step events still saves', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->withSlides(1)->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user)->post(route('presentations.rehearsal.store', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]), [
        'started_at' => now()->subMinute()->toISOString(),
        'ended_at' => now()->toISOString(),
        'duration_seconds' => 60,
        'slide_timings' => [],
    ])->assertRedirect();

    $run = Rehearsal::sole();

    expect($run->step_events)->toBe([])
        ->and($run->getFirstMedia(Rehearsal::RECORDING_COLLECTION))->toBeNull();
});

test('a non-audio upload is rejected as a rehearsal recording', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->withSlides(1)->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user)->postJson(route('presentations.rehearsal.store', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]), [
        'started_at' => now()->subMinute()->toISOString(),
        'ended_at' => now()->toISOString(),
        'duration_seconds' => 60,
        'slide_timings' => [],
        'audio' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
    ])->assertUnprocessable()->assertJsonValidationErrors('audio');

    expect(Rehearsal::count())->toBe(0);
});

test('the snapshot keeps the deck as it was even after the presentation is edited', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->withSlides(2)->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user)->post(route('presentations.rehearsal.store', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]), [
        'started_at' => now()->subMinutes(5)->toISOString(),
        'ended_at' => now()->toISOString(),
        'duration_seconds' => 300,
        'slide_timings' => [['slide' => 0, 'seconds' => 300]],
    ]);

    $snapshotBefore = Rehearsal::sole()->content;

    $presentation->update(['content' => ['version' => '1.0', 'slides' => []]]);

    expect(Rehearsal::sole()->content)->toBe($snapshotBefore)
        ->and($snapshotBefore['slides'])->toHaveCount(2);
});

test('a rehearsal cannot be recorded against another team\'s presentation', function () {
    $user = User::factory()->create();
    $foreignPresentation = Presentation::factory()->create();

    $this->actingAs($user)->post(route('presentations.rehearsal.store', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $foreignPresentation->id,
    ]), [
        'started_at' => now()->toISOString(),
        'ended_at' => now()->toISOString(),
        'duration_seconds' => 10,
        'slide_timings' => [],
    ])->assertNotFound();

    expect(Rehearsal::count())->toBe(0);
});

test('rehearsal timings are validated', function (array $payload) {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);

    $this->actingAs($user)->postJson(route('presentations.rehearsal.store', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $presentation->id,
    ]), $payload)->assertUnprocessable();
})->with([
    'missing started_at' => [['ended_at' => '2026-09-16T10:00:00Z', 'duration_seconds' => 60, 'slide_timings' => []]],
    'ends before it starts' => [['started_at' => '2026-09-16T10:00:00Z', 'ended_at' => '2026-09-16T09:00:00Z', 'duration_seconds' => 60, 'slide_timings' => []]],
    'negative duration' => [['started_at' => '2026-09-16T10:00:00Z', 'ended_at' => '2026-09-16T10:05:00Z', 'duration_seconds' => -5, 'slide_timings' => []]],
    'malformed slide timing' => [['started_at' => '2026-09-16T10:00:00Z', 'ended_at' => '2026-09-16T10:05:00Z', 'duration_seconds' => 60, 'slide_timings' => [['slide' => 'first']]]],
]);

test('the rehearsals page lists the team\'s rehearsals, most recent first', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->withSlides(2)->create([
        'team_id' => $user->currentTeam->id,
        'name' => 'Scaling Postgres',
    ]);
    $older = Rehearsal::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $user->currentTeam->id,
        'started_at' => now()->subDays(2),
    ]);
    $newer = Rehearsal::factory()->withSlideTimings([
        ['slide' => 0, 'seconds' => 90],
        ['slide' => 1, 'seconds' => 30],
    ])->create([
        'presentation_id' => $presentation->id,
        'team_id' => $user->currentTeam->id,
        'started_at' => now()->subHour(),
    ]);
    Rehearsal::factory()->create();

    $response = $this->actingAs($user)->get(route('rehearsals.index', [
        'current_team' => $user->currentTeam->slug,
    ]));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('rehearsals/Index')
        ->has('runs', 2)
        ->where('runs.0.id', $newer->id)
        ->where('runs.0.presentation_name', 'Scaling Postgres')
        ->where('runs.0.duration_seconds', 120)
        ->where('runs.1.id', $older->id),
    );
});

test('a rehearsal replay exposes the frozen deck and its timings', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->withSlides(2)->create(['team_id' => $user->currentTeam->id]);
    $run = Rehearsal::factory()->withSlideTimings([
        ['slide' => 0, 'seconds' => 45],
    ])->create([
        'presentation_id' => $presentation->id,
        'team_id' => $user->currentTeam->id,
        'content' => $presentation->content,
    ]);

    $response = $this->actingAs($user)->get(route('rehearsals.show', [
        'current_team' => $user->currentTeam->slug,
        'rehearsal' => $run->id,
    ]));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('rehearsals/Show')
        ->where('run.id', $run->id)
        ->where('run.duration_seconds', 45)
        ->has('run.slide_timings', 1)
        ->has('run.content.slides', 2),
    );
});

test('a snapshot envelope creates a new deck via the import flow', function () {
    $user = User::factory()->create();
    $presentation = Presentation::factory()->withSlides(2)->create(['team_id' => $user->currentTeam->id]);
    $run = Rehearsal::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $user->currentTeam->id,
        'content' => $presentation->content,
    ]);

    // The original deck moves on; the restore must come from the snapshot.
    $presentation->update(['content' => ['version' => '1.0', 'slides' => []]]);

    $response = $this->actingAs($user)->post(route('presentations.importJson', [
        'current_team' => $user->currentTeam->slug,
    ]), [
        'json' => json_encode([
            'name' => 'Restored rehearsal',
            'content' => $run->content,
            'flow' => $run->flow,
        ]),
    ]);

    $restored = Presentation::query()->where('name', 'Restored rehearsal')->sole();

    $response->assertRedirect(route('presentations.edit', [
        'current_team' => $user->currentTeam->slug,
        'presentation' => $restored->id,
    ]));

    expect($restored->content['slides'])->toHaveCount(2);
});

test('a rehearsal from another team is not reachable', function () {
    $user = User::factory()->create();
    $foreignRun = Rehearsal::factory()->create();

    $this->actingAs($user)->get(route('rehearsals.show', [
        'current_team' => $user->currentTeam->slug,
        'rehearsal' => $foreignRun->id,
    ]))->assertNotFound();
});
