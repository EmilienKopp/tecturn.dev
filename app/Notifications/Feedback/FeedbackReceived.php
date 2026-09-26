<?php

namespace App\Notifications\Feedback;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the admins whenever a user submits feedback.
 */
class FeedbackReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $message,
        public ?string $submittedBy = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('New :app feedback', ['app' => config('app.name')]))
            ->line(__('Someone just left feedback:'))
            ->line($this->message)
            ->line(__('From: :from', ['from' => $this->submittedBy ?? __('an anonymous user')]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'submitted_by' => $this->submittedBy,
        ];
    }
}
