<?php

declare(strict_types=1);

namespace App\Http\Controllers\Beta;

use App\Http\Controllers\Controller;
use App\Support\Features;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowBetaRegistrationController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless(Features::registration()->allowsBetaRequests(), 404);

        // Prefilled when a would-be user was bounced here from the login gate,
        // so they don't retype the name/email they just gave WorkOS.
        $prefill = $request->session()->get('beta_prefill', []);

        return Inertia::render('beta/register', [
            'prefill' => [
                'name' => $prefill['name'] ?? '',
                'email' => $prefill['email'] ?? '',
            ],
        ]);
    }
}
