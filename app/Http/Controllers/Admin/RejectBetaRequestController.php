<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Actions\Beta\RejectBetaRequest;
use App\Application\Commands\RejectBetaRequestCommand;
use App\Http\Controllers\Controller;
use App\Models\BetaRequestModel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class RejectBetaRequestController extends Controller
{
    public function __construct(private readonly RejectBetaRequest $rejectBetaRequest) {}

    public function __invoke(BetaRequestModel $betaRequest): RedirectResponse
    {
        $this->rejectBetaRequest->execute(new RejectBetaRequestCommand(
            betaRequestId: $betaRequest->id,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Request rejected.')]);

        return to_route('admin.beta-requests');
    }
}
