<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapters;

use App\Domain\Beta\Contracts\BetaInvitationGateway;
use App\Domain\Beta\Exceptions\BetaInvitationFailed;
use Laravel\WorkOS\WorkOS;
use Throwable;
use WorkOS\UserManagement;

/**
 * Provisions beta access by sending a WorkOS invitation. The invitee accepts
 * through AuthKit, at which point the local user record is created on their
 * first authenticated request (see routes/auth.php).
 */
class WorkOsInvitationGateway implements BetaInvitationGateway
{
    public function __construct(private readonly UserManagement $userManagement) {}

    public function invite(string $email): void
    {
        WorkOS::configure();

        try {
            $this->userManagement->sendInvitation($email);
        } catch (Throwable $e) {
            throw new BetaInvitationFailed(
                "Failed to send WorkOS invitation to {$email}: {$e->getMessage()}",
                previous: $e,
            );
        }
    }
}
