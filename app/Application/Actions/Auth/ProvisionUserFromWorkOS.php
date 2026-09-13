<?php

declare(strict_types=1);

namespace App\Application\Actions\Auth;

use App\Exceptions\RegistrationNotAllowed;
use App\Models\User;
use App\Support\RegistrationPolicy;
use Illuminate\Support\Str;
use Laravel\WorkOS\User as WorkOSUser;

/**
 * Bridges a WorkOS identity to a local account for the AuthKit callback.
 *
 * - find() is the "findUsing" hook: locate the account, reconciling by verified
 *   email when the WorkOS id changed (e.g. the WorkOS app/environment was
 *   switched) so we adopt the new id instead of colliding on the unique email.
 * - create() is the "createUsing" hook: only reached for genuinely new emails,
 *   and gated by the registration policy.
 *
 * Reconciliation lives in find() on purpose: createUsing fires a Registered
 * event (which provisions a personal team), so re-linking there would create a
 * duplicate team.
 */
class ProvisionUserFromWorkOS
{
    public function __construct(
        private readonly RegistrationPolicy $policy,
    ) {}

    public function find(WorkOSUser $workosUser): ?User
    {
        $user = User::query()->where('workos_id', $workosUser->id)->first();

        if ($user) {
            return $user;
        }

        // The WorkOS id didn't match, but WorkOS has verified this email, so an
        // account with the same address is the same person under a new id.
        $byEmail = User::query()
            ->whereRaw('lower(email) = ?', [Str::lower($workosUser->email)])
            ->first();

        if ($byEmail) {
            $byEmail->update(['workos_id' => $workosUser->id]);

            return $byEmail;
        }

        return null;
    }

    /**
     * @throws RegistrationNotAllowed when the email may not create an account
     */
    public function create(WorkOSUser $workosUser): User
    {
        if (! $this->policy->allowsRegistration($workosUser->email)) {
            throw new RegistrationNotAllowed(
                prefillEmail: $workosUser->email,
                prefillName: trim($workosUser->firstName.' '.$workosUser->lastName),
            );
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
