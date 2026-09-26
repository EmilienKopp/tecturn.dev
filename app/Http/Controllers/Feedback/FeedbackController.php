<?php

declare(strict_types=1);

namespace App\Http\Controllers\Feedback;

use App\Application\Actions\Feedback\SubmitFeedback;
use App\Application\Commands\SubmitFeedbackCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Feedback\SubmitFeedbackRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    public function __construct(private readonly SubmitFeedback $submitFeedback) {}

    public function create(): Response
    {
        return Inertia::render('feedback/Index');
    }

    public function store(SubmitFeedbackRequest $request): RedirectResponse
    {
        $this->submitFeedback->execute(new SubmitFeedbackCommand(
            message: $request->validated('message'),
            userId: $request->user()?->id,
        ));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Thanks for the feedback.'),
        ]);

        return to_route('feedback.create');
    }
}
