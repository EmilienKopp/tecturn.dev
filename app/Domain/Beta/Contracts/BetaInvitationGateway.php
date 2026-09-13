<?php

declare(strict_types=1);

namespace App\Domain\Beta\Contracts;

use App\Domain\Beta\Exceptions\BetaInvitationFailed;

interface BetaInvitationGateway
{
    /**
     * Invite the given address to create an account, granting them access to
     * the beta. The invited person completes signup through the identity
     * provider; the local user record is created on their first login.
     *
     * @throws BetaInvitationFailed when the invitation cannot be sent
     */
    public function invite(string $email): void;
}
