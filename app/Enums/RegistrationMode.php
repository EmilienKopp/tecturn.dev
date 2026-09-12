<?php

namespace App\Enums;

enum RegistrationMode: string
{
    case Open = 'open';
    case Invitation = 'invitation';
    case Closed = 'closed';

    /**
     * Anyone may sign in and create an account without an invitation.
     */
    public function allowsSelfRegistration(): bool
    {
        return $this === self::Open;
    }

    /**
     * Visitors may request access to the private beta.
     */
    public function allowsBetaRequests(): bool
    {
        return $this === self::Invitation;
    }
}
