<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Application\Actions\Presentations\RequestRehearsalReview;
use App\Application\Commands\RequestRehearsalReviewCommand;
use App\Domain\Presentation\Exceptions\DuplicateReviewRequest;
use App\Domain\Presentation\Exceptions\ReviewerDoesNotFollowRequester;
use App\Http\Controllers\Controller;
use App\Http\Requests\Presentations\RequestRehearsalReviewRequest;
use App\Models\PracticeRunModel;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RequestRehearsalReviewController extends Controller
{
    public function __construct(private readonly RequestRehearsalReview $requestReview) {}

    public function __invoke(RequestRehearsalReviewRequest $request, Team $current_team, PracticeRunModel $practice_run): RedirectResponse
    {
        Gate::authorize('view', $practice_run->presentation);

        try {
            $this->requestReview->execute(new RequestRehearsalReviewCommand(
                practiceRunId: $practice_run->id,
                requesterUserId: $request->user()->id,
                reviewerUserId: (int) $request->validated('reviewer_user_id'),
            ));
        } catch (ReviewerDoesNotFollowRequester|DuplicateReviewRequest $exception) {
            throw ValidationException::withMessages([
                'reviewer_user_id' => $exception->getMessage(),
            ]);
        }

        return redirect()->back();
    }
}
