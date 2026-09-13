<?php

declare(strict_types=1);

namespace App\Support;

use App\Domain\Beta\Contracts\BetaRequestRepository;
use App\Enums\RegistrationMode;
use Illuminate\Support\Str;

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
        // Admins always get in, whatever the mode: otherwise no one could ever
        // log in to approve requests once registration is invitation/closed.
        if ($this->isAdmin($email)) {
            return true;
        }

        return match (Features::registration()) {
            RegistrationMode::Open => true,
            RegistrationMode::Invitation => $this->betaRequests->hasApprovedRequestForEmail($email),
            RegistrationMode::Closed => false,
        };
    }

    private function isAdmin(string $email): bool
    {
        return in_array(Str::lower(trim($email)), config('admin.emails', []), true);
    }
}
