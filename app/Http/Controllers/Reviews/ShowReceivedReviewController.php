<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reviews;

use App\Http\Controllers\Controller;
use App\Infrastructure\ReadModels\RehearsalReviewReadModel;
use App\Models\RehearsalReviewModel;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShowReceivedReviewController extends Controller
{
    public function __construct(private readonly RehearsalReviewReadModel $reviews) {}

    public function __invoke(RehearsalReviewModel $rehearsal_review): Response
    {
        Gate::authorize('viewReceived', $rehearsal_review);

        $review = $this->reviews->findForReviewPage($rehearsal_review->id);

        return Inertia::render('reviews/Received', [
            'review' => $review,
            'audioUrl' => $review['has_recording']
                ? route('rehearsals.audio', ['rehearsal' => $review['practice_run_id']])
                : null,
            'siblingReviews' => $this->reviews->listSiblingReviews(
                $review['practice_run_id'],
                $review['id'],
            ),
            'otherRuns' => $this->reviews->listReviewedRunsForPresentation(
                $review['presentation_id'],
                $rehearsal_review->requester_user_id,
                $review['practice_run_id'],
            ),
        ]);
    }
}
