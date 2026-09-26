<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reviews;

use App\Http\Controllers\Controller;
use App\Infrastructure\ReadModels\RehearsalReviewReadModel;
use App\Models\RehearsalReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RehearsalReviewController extends Controller
{
    public function __construct(private readonly RehearsalReviewReadModel $reviews) {}

    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        return Inertia::render('reviews/Index', [
            'received' => $this->reviews->listForReviewer($userId),
            'requested' => $this->reviews->listForRequester($userId),
        ]);
    }

    public function show(RehearsalReview $rehearsal_review): Response
    {
        Gate::authorize('view', $rehearsal_review);

        $review = $this->reviews->findForReviewPage($rehearsal_review->id);

        return Inertia::render('reviews/Show', [
            'review' => $review,
            'audioUrl' => $review['has_recording']
                ? route('rehearsals.audio', ['rehearsal' => $review['practice_run_id']])
                : null,
        ]);
    }
}
