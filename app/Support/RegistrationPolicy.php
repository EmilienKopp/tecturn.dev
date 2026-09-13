<?php

declare(strict_types=1);

namespace App\Support;

use App\Domain\Beta\Contracts\BetaRequestRepository;
use App\Enums\RegistrationMode;

/**
 * Decides whether a brand-new identity coming back from WorkOS is allowed to
 * become a local account. This is the gate that enforces the current
 * registration mode at authentication time.
 */
class RegistrationPolicy
{
    public function __construct(
        private readonly BetaRequestRepository $betaRequests,
    ) {}

    /**
     * Whether an account may be created for the given email address.
     */
    public function allowsRegistration(string $email): bool
    {
        return match (Features::registration()) {
            RegistrationMode::Open => true,
            RegistrationMode::Invitation => $this->betaRequests->hasApprovedRequestForEmail($email),
            RegistrationMode::Closed => false,
        };
    }
}
