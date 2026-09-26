<?php

declare(strict_types=1);

use App\Models\Presentation;
use App\Models\Rehearsal;
use App\Models\RehearsalReview;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/** @return array{User, Presentation, Rehearsal} */
function requesterWithRun(): array
{
    $requester = User::factory()->create();
    $presentation = Presentation::factory()->withSlides(2)->create(['team_id' => $requester->currentTeam->id]);
    $run = Rehearsal::factory()->withStepEvents()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $requester->currentTeam->id,
        'content' => $presentation->content,
    ]);

    return [$requester, $presentation, $run];
}

test('the reviews index splits reviews by side', function () {
    [$requester, , $run] = requesterWithRun();
    $reviewer = User::factory()->create();
    $review = RehearsalReview::factory()->create([
        'practice_run_id' => $run->id,
        'requester_user_id' => $requester->id,
        'reviewer_user_id' => $reviewer->id,
    ]);

    $this->actingAs($reviewer)->get(route('reviews.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reviews/Index')
            ->has('received', 1)
            ->where('received.0.id', $review->id)
            ->where('received.0.requester_name', $requester->name)
            ->has('requested', 0),
        );

    $this->actingAs($requester)->get(route('reviews.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reviews/Index')
            ->has('received', 0)
            ->has('requested', 1)
            ->where('requested.0.id', $review->id)
            ->where('requested.0.reviewer_name', $reviewer->name),
        );
});

test('the requester sees a received review with sibling and other-run shortcuts', function () {
    [$requester, $presentation, $run] = requesterWithRun();
    $reviewer = User::factory()->create(['name' => 'Aiko Reviewer']);
    $review = RehearsalReview::factory()->completed()->create([
        'practice_run_id' => $run->id,
        'requester_user_id' => $requester->id,
        'reviewer_user_id' => $reviewer->id,
    ]);
    $review->comments()->create(['slide_number' => 0, 'message' => 'Great opener.']);

    // A second review on the same run, by someone else.
    $sibling = RehearsalReview::factory()->create([
        'practice_run_id' => $run->id,
        'requester_user_id' => $requester->id,
    ]);

    // A reviewed rehearsal of the same presentation.
    $otherRun = Rehearsal::factory()->withStepEvents()->create([
        'presentation_id' => $presentation->id,
        'team_id' => $requester->currentTeam->id,
        'content' => $presentation->content,
    ]);
    $otherReview = RehearsalReview::factory()->create([
        'practice_run_id' => $otherRun->id,
        'requester_user_id' => $requester->id,
    ]);

    $this->actingAs($requester)->get(route('reviews.received', ['rehearsal_review' => $review->id]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reviews/Received')
            ->where('review.id', $review->id)
            ->where('review.reviewer_name', 'Aiko Reviewer')
            ->where('review.comments.0.message', 'Great opener.')
            ->has('review.content.slides', 2)
            ->has('siblingReviews', 1)
            ->where('siblingReviews.0.id', $sibling->id)
            ->has('otherRuns', 1)
            ->where('otherRuns.0.practice_run_id', $otherRun->id)
            ->where('otherRuns.0.reviews.0.id', $otherReview->id),
        );
});

test('only the requester can open the received review page', function () {
    [$requester, , $run] = requesterWithRun();
    $reviewer = User::factory()->create();
    $review = RehearsalReview::factory()->create([
        'practice_run_id' => $run->id,
        'requester_user_id' => $requester->id,
        'reviewer_user_id' => $reviewer->id,
    ]);
    $stranger = User::factory()->create();

    $this->actingAs($reviewer)->get(route('reviews.received', ['rehearsal_review' => $review->id]))->assertForbidden();
    $this->actingAs($stranger)->get(route('reviews.received', ['rehearsal_review' => $review->id]))->assertForbidden();
});
