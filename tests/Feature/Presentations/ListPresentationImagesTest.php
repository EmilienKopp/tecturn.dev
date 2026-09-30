<?php

declare(strict_types=1);

use App\Models\Presentation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('it lists content images across the team presentations, newest first', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $deckOne = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);
    $deckTwo = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);

    $deckOne->addMedia(UploadedFile::fake()->image('older.png'))
        ->toMediaCollection(Presentation::IMAGES_COLLECTION);
    $deckTwo->addMedia(UploadedFile::fake()->image('newer.png'))
        ->toMediaCollection(Presentation::IMAGES_COLLECTION);

    $response = $this
        ->actingAs($user)
        ->getJson(route('presentations.images.index', [
            'current_team' => $user->currentTeam->slug,
        ]));

    $response->assertOk();

    $images = $response->json('images');
    expect($images)->toHaveCount(2);
    expect($images[0]['url'])->toContain('newer');
    expect($images[1]['url'])->toContain('older');
    expect($images[0]['presentation_id'])->toBe($deckTwo->id);
});

test('it excludes background images and images from other teams', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $deck = Presentation::factory()->create(['team_id' => $user->currentTeam->id]);

    // Background media lives in a different collection and must not appear.
    $deck->addMedia(UploadedFile::fake()->image('backdrop.jpg'))
        ->toMediaCollection(Presentation::BACKGROUND_COLLECTION);

    // Another team's content image must not leak into this team's library.
    $otherUser = User::factory()->create();
    $otherDeck = Presentation::factory()->create(['team_id' => $otherUser->currentTeam->id]);
    $otherDeck->addMedia(UploadedFile::fake()->image('secret.png'))
        ->toMediaCollection(Presentation::IMAGES_COLLECTION);

    $response = $this
        ->actingAs($user)
        ->getJson(route('presentations.images.index', [
            'current_team' => $user->currentTeam->slug,
        ]));

    $response->assertOk();
    expect($response->json('images'))->toBe([]);
});

test('a non member cannot list a team library', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();

    $response = $this
        ->actingAs($stranger)
        ->getJson(route('presentations.images.index', [
            'current_team' => $user->currentTeam->slug,
        ]));

    $response->assertForbidden();
});
