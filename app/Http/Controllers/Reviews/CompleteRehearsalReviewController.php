<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reviews;

use App\Application\Actions\Presentations\CompleteRehearsalReview;
use App\Application\Commands\CompleteRehearsalReviewCommand;
use App\Http\Controllers\Controller;
use App\Models\RehearsalReviewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CompleteRehearsalReviewController extends Controller
{
    public function __construct(private readonly CompleteRehearsalReview $completeReview) {}

    public function __invoke(RehearsalReviewModel $rehearsal_review): RedirectResponse
    {
        Gate::authorize('complete', $rehearsal_review);

        $this->completeReview->execute(new CompleteRehearsalReviewCommand(
            rehearsalReviewId: $rehearsal_review->id,
        ));

        return redirect()->back();
    }
}
