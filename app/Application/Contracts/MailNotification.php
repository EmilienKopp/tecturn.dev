<?php

namespace App\Application\Contracts;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class MailNotification extends Notification implements NotificationLike
{
    abstract public function toMail(object $notifiable): MailMessage;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}
