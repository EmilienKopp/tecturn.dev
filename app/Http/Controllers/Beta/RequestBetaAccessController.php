<?php

declare(strict_types=1);

namespace App\Http\Controllers\Beta;

use App\Application\Actions\Beta\RequestBetaAccess;
use App\Application\Commands\RequestBetaAccessCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Beta\RequestBetaAccessRequest;
use App\Support\Features;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class RequestBetaAccessController extends Controller
{
    public function __construct(private readonly RequestBetaAccess $requestBetaAccess) {}

    public function __invoke(RequestBetaAccessRequest $request): RedirectResponse
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
