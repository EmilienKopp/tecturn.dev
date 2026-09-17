<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reviews;

use App\Http\Controllers\Controller;
use App\Infrastructure\ReadModels\RehearsalReviewReadModel;
use App\Models\RehearsalReviewModel;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShowRehearsalReviewController extends Controller
{
    public function __construct(private readonly RehearsalReviewReadModel $reviews) {}

    public function __invoke(RehearsalReviewModel $rehearsal_review): Response
    {
        Gate::authorize('view', $rehearsal_review);

        $review = $this->reviews->findForReviewPage($rehearsal_review->id);

        return Inertia::render('reviews/Show', [
            'review' => $review,
            'audioUrl' => $review['has_recording']
                ? route('rehearsals.audio', ['practice_run' => $review['practice_run_id']])
                : null,
        ]);
    }
}
