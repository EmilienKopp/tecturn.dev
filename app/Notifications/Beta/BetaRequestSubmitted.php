<?php

namespace App\Notifications\Beta;

use App\Application\Contracts\MailNotification;
use App\Domain\Beta\Entities\BetaRequestEntity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Alert sent to the admins when a visitor requests private beta access.
 */
class BetaRequestSubmitted extends MailNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BetaRequestEntity $betaRequest) {}

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('New beta request from :name', ['name' => $this->betaRequest->name]))
            ->line(__('Someone just requested access to the private beta.'))
            ->line(__('Name: :name', ['name' => $this->betaRequest->name]))
            ->line(__('Email: :email', ['email' => $this->betaRequest->email]));

        if ($this->betaRequest->message !== null) {
            $message->line(__('Message: :message', ['message' => $this->betaRequest->message]));
        }

        return $message->action(__('Review requests'), route('admin.beta-requests'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'name' => $this->betaRequest->name,
            'email' => $this->betaRequest->email,
        ];
    }
}
