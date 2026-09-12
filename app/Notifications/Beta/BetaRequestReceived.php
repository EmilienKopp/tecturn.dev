<?php

namespace App\Notifications\Beta;

use App\Domain\Beta\Entities\BetaRequestEntity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirmation sent to a visitor after they request private beta access.
 */
class BetaRequestReceived extends Notification implements ShouldQueue
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
            ->subject(__('Thanks for requesting :app beta access', ['app' => config('app.name')]))
            ->greeting(__('Hi :name,', ['name' => $this->betaRequest->name]))
            ->line(__("We've received your request to join the :app private beta.", ['app' => config('app.name')]))
            ->line(__("We're letting people in gradually. We'll email you as soon as your spot is ready."))
            ->line(__('Thanks for your interest!'));
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
