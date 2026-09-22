<?php

declare(strict_types=1);

namespace App\Http\Controllers\Beta;

use App\Application\Actions\Beta\RequestBetaAccess;
use App\Application\Commands\RequestBetaAccessCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Beta\RequestBetaAccessRequest;
use App\Support\Features;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BetaRequestController extends Controller
{
    public function __construct(private readonly RequestBetaAccess $requestBetaAccess) {}

    public function create(Request $request): Response
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

    public function store(RequestBetaAccessRequest $request): RedirectResponse
    {
        abort_unless(Features::registration()->allowsBetaRequests(), 404);

        $this->requestBetaAccess->execute(new RequestBetaAccessCommand(
            name: $request->validated('name'),
            email: $request->validated('email'),
            message: $request->validated('message'),
        ));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __("You're on the list. Check your inbox for a confirmation."),
        ]);

        return to_route('home');
    }
}
