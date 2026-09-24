<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reviews;

use App\Application\Actions\Presentations\AddReviewComment;
use App\Application\Commands\AddReviewCommentCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reviews\AddReviewCommentRequest;
use App\Models\RehearsalReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AddReviewCommentController extends Controller
{
    public function __construct(private readonly AddReviewComment $addComment) {}

    public function __invoke(AddReviewCommentRequest $request, RehearsalReview $rehearsal_review): RedirectResponse
    {
        Gate::authorize('comment', $rehearsal_review);

        $this->addComment->execute(new AddReviewCommentCommand(
            rehearsalReviewId: $rehearsal_review->id,
            slideNumber: (int) $request->validated('slide_number'),
            message: (string) $request->validated('message'),
        ));

        return redirect()->back();
    }
}
