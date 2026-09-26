<?php

namespace App\Listeners\Feedback;

use App\Application\Events\FeedbackCreated;
use App\Domain\Feedback\Entities\FeedbackEntity;
use App\Models\User;
use App\Notifications\Feedback\FeedbackReceived;
use Illuminate\Support\Facades\Notification;

class FeedbackCreatedListener
{
    /**
     * Notify the admins that a new piece of feedback came in.
     */
    public function handle(FeedbackCreated $event): void
    {
        $feedback = $event->entity();

        if (! $feedback instanceof FeedbackEntity) {
            return;
        }

        $admins = config('admin.emails', []);

        if ($admins === []) {
            return;
        }

        Notification::route('mail', $admins)
            ->notify(new FeedbackReceived(
                message: $feedback->message,
                submittedBy: $this->submitterFor($feedback->user_id),
            ));
    }

    /**
     * A "Name <email>" label for the submitting user, when known.
     */
    private function submitterFor(?int $userId): ?string
    {
        if ($userId === null) {
            return null;
        }

        $user = User::find($userId);

        return $user ? "{$user->name} <{$user->email}>" : null;
    }
}
