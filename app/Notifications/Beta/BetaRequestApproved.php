<?php

namespace App\Notifications\Beta;

use App\Domain\Beta\Entities\BetaRequestEntity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a requester once an admin approves their private beta access.
 */
class BetaRequestApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BetaRequestEntity $betaRequest) {}

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
            ->subject(__("You're in! :app beta access is ready", ['app' => config('app.name')]))
            ->greeting(__('Hi :name,', ['name' => $this->betaRequest->name]))
            ->line(__("Good news, your request to join the :app private beta was approved.", ['app' => config('app.name')]))
            ->action(__('Sign in to get started'), route('login'))
            ->line(__('Welcome aboard!'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'email' => $this->betaRequest->email,
        ];
    }
}
