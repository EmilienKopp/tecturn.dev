<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Actions\Beta\ApproveBetaRequest;
use App\Application\Commands\ApproveBetaRequestCommand;
use App\Http\Controllers\Controller;
use App\Models\BetaRequestModel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ApproveBetaRequestController extends Controller
{
    public function __construct(private readonly ApproveBetaRequest $approveBetaRequest) {}

    public function __invoke(BetaRequestModel $betaRequest): RedirectResponse
    {
        $this->approveBetaRequest->execute(new ApproveBetaRequestCommand(
            betaRequestId: $betaRequest->id,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Request approved.')]);

        return to_route('admin.beta-requests');
    }
}
