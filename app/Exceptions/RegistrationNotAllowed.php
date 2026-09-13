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
 * request form, otherwise back to the landing page.
 */
class RegistrationNotAllowed extends RuntimeException
{
    public function render(Request $request): RedirectResponse
    {
        $canRequestAccess = Features::registration()->allowsBetaRequests();

        Inertia::flash('toast', [
            'type' => 'error',
            'message' => $canRequestAccess
                ? __('You need an approved invite to sign in. Request access below.')
                : __('This email has not been approved for access yet.'),
        ]);

        return redirect()->to($canRequestAccess ? route('beta.create') : route('home'));
    }
}
