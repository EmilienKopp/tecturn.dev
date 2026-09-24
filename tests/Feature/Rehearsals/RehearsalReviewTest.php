<?php

declare(strict_types=1);

use App\Models\Presentation;
use App\Models\Rehearsal;
use App\Models\RehearsalReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

function follow(User $follower, User $followed): void
{
    DB::table('user_follows')->insert([
        'follower_user_id' => $follower->id,
        'followed_user_id' => $followed->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

/** @return array{User, Rehearsal} */
function runForUser(): array
{
    $user = User::factory()->create();
    $presentation = Presentation::factory()->withSlides(2)->create(['team_id' => $user->currentTeam->id]);
    $run = Rehearsal::factory()->withStepEvents()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $user->currentTeam->id,
        'content' => $presentation->content,
    ]);

    return [$user, $run];
}

test('a review can be requested from a follower', function () {
    [$requester, $run] = runForUser();
    $reviewer = User::factory()->create();
    follow($reviewer, $requester);

    $this->actingAs($requester)->post(route('rehearsals.reviews.store', [
        'current_team' => $requester->currentTeam->slug,
        'rehearsal' => $run->id,
    ]), ['reviewer_user_id' => $reviewer->id])->assertRedirect();

    $review = RehearsalReview::sole();

    expect($review->practice_run_id)->toBe($run->id)
        ->and($review->requester_user_id)->toBe($requester->id)
        ->and($review->reviewer_user_id)->toBe($reviewer->id)
        ->and($review->status)->toBe('pending');
});

test('a review cannot be requested from someone who does not follow the requester', function () {
    [$requester, $run] = runForUser();
    $stranger = User::factory()->create();

    $this->actingAs($requester)->from(route('rehearsals.show', [
        'current_team' => $requester->currentTeam->slug,
        'rehearsal' => $run->id,
    ]))->post(route('rehearsals.reviews.store', [
        'current_team' => $requester->currentTeam->slug,
        'rehearsal' => $run->id,
    ]), ['reviewer_user_id' => $stranger->id])->assertSessionHasErrors('reviewer_user_id');

    expect(RehearsalReview::count())->toBe(0);
});

test('the same person cannot be asked twice for one run', function () {
    [$requester, $run] = runForUser();
    $reviewer = User::factory()->create();
    follow($reviewer, $requester);
    RehearsalReview::factory()->create([
        'practice_run_id' => $run->id,
        'requester_user_id' => $requester->id,
        'reviewer_user_id' => $reviewer->id,
    ]);

    $this->actingAs($requester)->post(route('rehearsals.reviews.store', [
        'current_team' => $requester->currentTeam->slug,
        'rehearsal' => $run->id,
    ]), ['reviewer_user_id' => $reviewer->id])->assertSessionHasErrors('reviewer_user_id');

    expect(RehearsalReview::count())->toBe(1);
});

test('the reviewer sees the review page with the frozen snapshot', function () {
    [$requester, $run] = runForUser();
    $reviewer = User::factory()->create();
    $review = RehearsalReview::factory()->create([
        'practice_run_id' => $run->id,
        'requester_user_id' => $requester->id,
        'reviewer_user_id' => $reviewer->id,
    ]);

    $response = $this->actingAs($reviewer)->get(route('reviews.show', ['rehearsal_review' => $review->id]));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('reviews/Show')
        ->where('review.id', $review->id)
        ->where('review.status', 'pending')
        ->has('review.content.slides', 2)
        ->has('review.step_events', 3)
        ->has('review.comments', 0),
    );
});

test('only the assigned reviewer can open the review page', function () {
    [$requester, $run] = runForUser();
    $review = RehearsalReview::factory()->create([
        'practice_run_id' => $run->id,
        'requester_user_id' => $requester->id,
    ]);
    $stranger = User::factory()->create();

    $this->actingAs($requester)->get(route('reviews.show', ['rehearsal_review' => $review->id]))->assertForbidden();
    $this->actingAs($stranger)->get(route('reviews.show', ['rehearsal_review' => $review->id]))->assertForbidden();
});

test('the reviewer can comment on a slide', function () {
    [$requester, $run] = runForUser();
    $reviewer = User::factory()->create();
    $review = RehearsalReview::factory()->create([
        'practice_run_id' => $run->id,
        'requester_user_id' => $requester->id,
        'reviewer_user_id' => $reviewer->id,
    ]);

    $this->actingAs($reviewer)->post(route('reviews.comments.store', ['rehearsal_review' => $review->id]), [
        'slide_number' => 1,
        'message' => 'Slow down here, the diagram needs a beat.',
    ])->assertRedirect();

    $comment = $review->comments()->sole();

    expect($comment->slide_number)->toBe(1)
        ->and($comment->message)->toBe('Slow down here, the diagram needs a beat.');
});

test('nobody but the reviewer can comment', function () {
    [$requester, $run] = runForUser();
    $review = RehearsalReview::factory()->create([
        'practice_run_id' => $run->id,
        'requester_user_id' => $requester->id,
    ]);

    $this->actingAs($requester)->post(route('reviews.comments.store', ['rehearsal_review' => $review->id]), [
        'slide_number' => 0,
        'message' => 'Sneaky self-review.',
    ])->assertForbidden();
});

test('a completed review no longer accepts comments', function () {
    [$requester, $run] = runForUser();
    $reviewer = User::factory()->create();
    $review = RehearsalReview::factory()->completed()->create([
        'practice_run_id' => $run->id,
        'requester_user_id' => $requester->id,
        'reviewer_user_id' => $reviewer->id,
    ]);

    $this->actingAs($reviewer)->post(route('reviews.comments.store', ['rehearsal_review' => $review->id]), [
        'slide_number' => 0,
        'message' => 'Too late.',
    ])->assertForbidden();

    expect($review->comments()->count())->toBe(0);
});

test('the reviewer can mark the review complete', function () {
    [$requester, $run] = runForUser();
    $reviewer = User::factory()->create();
    $review = RehearsalReview::factory()->create([
        'practice_run_id' => $run->id,
        'requester_user_id' => $requester->id,
        'reviewer_user_id' => $reviewer->id,
    ]);

    $this->actingAs($reviewer)->post(route('reviews.complete', ['rehearsal_review' => $review->id]))->assertRedirect();

    expect($review->refresh()->status)->toBe('completed');
});

test('the requester sees reviews and comments on the rehearsal replay page', function () {
    [$requester, $run] = runForUser();
    $reviewer = User::factory()->create(['name' => 'Aiko Reviewer']);
    $review = RehearsalReview::factory()->create([
        'practice_run_id' => $run->id,
        'requester_user_id' => $requester->id,
        'reviewer_user_id' => $reviewer->id,
    ]);
    $review->comments()->create(['slide_number' => 0, 'message' => 'Great opener.']);

    $response = $this->actingAs($requester)->get(route('rehearsals.show', [
        'current_team' => $requester->currentTeam->slug,
        'rehearsal' => $run->id,
    ]));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('rehearsals/Show')
        ->has('reviews', 1)
        ->where('reviews.0.reviewer_name', 'Aiko Reviewer')
        ->where('reviews.0.comments.0.message', 'Great opener.')
        // Strict: view columns must be cast, or the page renders "01".
        ->where('reviews.0.comments.0.slide_number', 0)
        ->has('followers'),
    );
});

test('the rehearsals index lists reviews requested of the signed-in user', function () {
    [$requester, $run] = runForUser();
    $reviewer = User::factory()->create();
    RehearsalReview::factory()->create([
        'practice_run_id' => $run->id,
        'requester_user_id' => $requester->id,
        'reviewer_user_id' => $reviewer->id,
    ]);

    $response = $this->actingAs($reviewer)->get(route('rehearsals.index', [
        'current_team' => $reviewer->currentTeam->slug,
    ]));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('rehearsals/Index')
        ->has('reviewRequests', 1)
        ->where('reviewRequests.0.status', 'pending')
        ->where('reviewRequests.0.requester_name', $requester->name),
    );
});
