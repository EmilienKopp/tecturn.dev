<?php

declare(strict_types=1);

use App\Models\PracticeRunModel;
use App\Models\PresentationModel;
use App\Models\RehearsalReviewModel;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/** @return array{User, PracticeRunModel} */
function runWithRecording(): array
{
    Storage::fake('public');

    $user = User::factory()->create();
    $presentation = PresentationModel::factory()->withSlides(1)->create(['team_id' => $user->currentTeam->id]);
    $run = PracticeRunModel::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $user->currentTeam->id,
    ]);

    $path = tempnam(sys_get_temp_dir(), 'rehearsal-audio-');
    file_put_contents($path, hex2bin('1a45dfa39f4286810142f7810142f2810442f381084282847765626d').str_repeat("\x00", 128));
    $run->addMedia($path)
        ->usingFileName('rehearsal.webm')
        ->toMediaCollection(PracticeRunModel::RECORDING_COLLECTION);

    return [$user, $run];
}

test('a team member can stream the rehearsal recording', function () {
    [$owner, $run] = runWithRecording();

    $this->actingAs($owner)->get(route('rehearsals.audio', ['practice_run' => $run->id]))->assertSuccessful();
});

test('an assigned reviewer can stream the recording', function () {
    [$owner, $run] = runWithRecording();
    $reviewer = User::factory()->create();
    RehearsalReviewModel::factory()->create([
        'practice_run_id' => $run->id,
        'requester_user_id' => $owner->id,
        'reviewer_user_id' => $reviewer->id,
    ]);

    $this->actingAs($reviewer)->get(route('rehearsals.audio', ['practice_run' => $run->id]))->assertSuccessful();
});

test('a stranger cannot stream the recording', function () {
    [, $run] = runWithRecording();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get(route('rehearsals.audio', ['practice_run' => $run->id]))->assertForbidden();
});

test('a run without a recording returns 404', function () {
    $user = User::factory()->create();
    $presentation = PresentationModel::factory()->withSlides(1)->create(['team_id' => $user->currentTeam->id]);
    $run = PracticeRunModel::factory()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $user->currentTeam->id,
    ]);

    $this->actingAs($user)->get(route('rehearsals.audio', ['practice_run' => $run->id]))->assertNotFound();
});
