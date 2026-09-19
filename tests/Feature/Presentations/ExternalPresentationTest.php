<?php

declare(strict_types=1);

use App\Models\PresentationModel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/** A minimal byte string finfo detects as application/pdf. */
function fakePdf(string $name = 'deck.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF",
    );
}

test('a Google Slides presentation is created and stores its source', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $url = 'https://docs.google.com/presentation/d/e/2PACX-abc/pub';

    $response = $this
        ->actingAs($user)
        ->post(route('presentations.store', ['current_team' => $team->slug]), [
            'name' => 'Conference keynote',
            'source_type' => 'google_slides',
            'external_url' => $url,
        ]);

    $presentation = PresentationModel::query()->firstOrFail();

    $response->assertRedirect(route('presentations.edit', [
        'current_team' => $team->slug,
        'presentation' => $presentation->id,
    ]));

    expect($presentation->source['type'])->toBe('google_slides')
        ->and($presentation->source['externalUrl'])->toBe($url);
});

test('a PDF presentation is created and stores the uploaded file', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->post(route('presentations.store', ['current_team' => $team->slug]), [
            'name' => 'Uploaded deck',
            'source_type' => 'pdf',
            'file' => fakePdf(),
        ]);

    $presentation = PresentationModel::query()->firstOrFail();

    $response->assertRedirect(route('presentations.edit', [
        'current_team' => $team->slug,
        'presentation' => $presentation->id,
    ]));

    expect($presentation->source['type'])->toBe('pdf')
        ->and($presentation->sourcePdfUrl())->not->toBeNull()
        ->and($presentation->getMedia(PresentationModel::SOURCE_COLLECTION))->toHaveCount(1);
});

test('a plain presentation defaults to the editor source', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->post(route('presentations.store', ['current_team' => $user->currentTeam->slug]), [
            'name' => 'Editor deck',
        ]);

    $presentation = PresentationModel::query()->firstOrFail();

    expect($presentation->source['type'])->toBe('editor');
});

test('a Google Slides deck requires a valid Google Slides url', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->post(route('presentations.store', ['current_team' => $user->currentTeam->slug]), [
            'name' => 'Bad link',
            'source_type' => 'google_slides',
            'external_url' => 'https://example.com/not-slides',
        ])
        ->assertSessionHasErrors('external_url');
});

test('a Google Slides deck rejects a missing url', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->post(route('presentations.store', ['current_team' => $user->currentTeam->slug]), [
            'name' => 'No link',
            'source_type' => 'google_slides',
        ])
        ->assertSessionHasErrors('external_url');
});

test('a PDF deck rejects a missing file', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->post(route('presentations.store', ['current_team' => $user->currentTeam->slug]), [
            'name' => 'No file',
            'source_type' => 'pdf',
        ])
        ->assertSessionHasErrors('file');
});

test('a PDF deck rejects a non-pdf file', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->post(route('presentations.store', ['current_team' => $user->currentTeam->slug]), [
            'name' => 'Wrong type',
            'source_type' => 'pdf',
            'file' => UploadedFile::fake()->image('slide.png'),
        ])
        ->assertSessionHasErrors('file');
});

test('an external deck opens the shared editor shell with its source', function () {
    $user = User::factory()->create();
    $presentation = PresentationModel::factory()->googleSlides()->create([
        'team_id' => $user->currentTeam->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('presentations.edit', [
            'current_team' => $user->currentTeam->slug,
            'presentation' => $presentation->id,
        ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('presentations/Editor')
        ->where('presentation.source.type', 'google_slides')
        ->where('sourcePdfUrl', null)
        ->has('viewerUrl'),
    );
});

test('the present page carries the external source and pdf url', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $presentation = PresentationModel::factory()->pdf()->create([
        'team_id' => $user->currentTeam->id,
    ]);

    $presentation
        ->addMedia(fakePdf())
        ->toMediaCollection(PresentationModel::SOURCE_COLLECTION);

    $response = $this
        ->actingAs($user)
        ->get(route('presentations.present', [
            'current_team' => $user->currentTeam->slug,
            'presentation' => $presentation->id,
        ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('presentations/Present')
        ->where('presentation.source.type', 'pdf')
        ->whereNot('sourcePdfUrl', null),
    );
});

test('an editor deck presents with a null pdf url', function () {
    $user = User::factory()->create();
    $presentation = PresentationModel::factory()->withSlides(1)->create([
        'team_id' => $user->currentTeam->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('presentations.present', [
            'current_team' => $user->currentTeam->slug,
            'presentation' => $presentation->id,
        ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('presentation.source.type', 'editor')
        ->where('sourcePdfUrl', null),
    );
});
