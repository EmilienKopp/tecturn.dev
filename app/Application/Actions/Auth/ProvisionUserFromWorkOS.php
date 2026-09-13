<?php

declare(strict_types=1);

namespace App\Application\Actions\Auth;

use App\Exceptions\RegistrationNotAllowed;
use App\Models\User;
use App\Support\RegistrationPolicy;
use Laravel\WorkOS\User as WorkOSUser;

/**
 * Creates the local account for a first-time WorkOS identity, but only if the
 * registration policy allows their email. Wired into the AuthKit callback as
 * the "createUsing" hook, so it runs for new identities regardless of which
 * login method (Google, GitHub, email) WorkOS used.
 */
class ProvisionUserFromWorkOS
{
    public function __construct(
        private readonly RegistrationPolicy $policy,
    ) {}

    /**
     * @throws RegistrationNotAllowed when the email may not create an account
     */
    public function __invoke(WorkOSUser $workosUser): User
    {
        if (! $this->policy->allowsRegistration($workosUser->email)) {
            throw new RegistrationNotAllowed;
        }

        return User::create([
            'name' => trim($workosUser->firstName.' '.$workosUser->lastName),
            'email' => $workosUser->email,
            'email_verified_at' => now(),
            'workos_id' => $workosUser->id,
            'avatar' => $workosUser->avatar ?? '',
        ]);
    }
}
