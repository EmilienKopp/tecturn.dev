<?php

namespace App\Application\Contracts;

interface NotificationLike
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array;
}
