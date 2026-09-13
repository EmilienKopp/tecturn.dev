<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\Features;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;

/**
 * Thrown when someone authenticates with WorkOS but the registration policy
 * won't provision an account for their email. Renders a friendly redirect
 * rather than an error page: in invitation mode we funnel them to the beta
 * request form (prefilled with the identity they just authenticated with, so
 * they don't retype it), otherwise back to the landing page.
 */
class RegistrationNotAllowed extends RuntimeException
{
    public function __construct(
        private readonly ?string $prefillEmail = null,
        private readonly ?string $prefillName = null,
    ) {
        parent::__construct('Registration is not allowed for this account.');
    }

    public function render(Request $request): RedirectResponse
    {
        $canRequestAccess = Features::registration()->allowsBetaRequests();

        Inertia::flash('toast', [
            'type' => 'error',
            'message' => $canRequestAccess
                ? __('You need an approved invite to sign in. Request access below.')
                : __('This email has not been approved for access yet.'),
        ]);

        if (! $canRequestAccess) {
            return redirect()->to(route('home'));
        }

        return redirect()->to(route('beta.create'))->with('beta_prefill', [
            'name' => $this->prefillName ?? '',
            'email' => $this->prefillEmail ?? '',
        ]);
    }
}
