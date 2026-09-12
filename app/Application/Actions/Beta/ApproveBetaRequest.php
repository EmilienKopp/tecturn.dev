<?php

declare(strict_types=1);

namespace App\Application\Actions\Beta;

use App\Application\Commands\ApproveBetaRequestCommand;
use App\Domain\Beta\Contracts\BetaRequestRepository;
use App\Domain\Beta\Entities\BetaRequestEntity;
use App\Notifications\Beta\BetaRequestApproved;
use Illuminate\Support\Facades\Notification;

class ApproveBetaRequest
{
    public function __construct(
        private readonly BetaRequestRepository $betaRequests,
    ) {}

    /**
     * Approve a pending beta request and let the requester know they're in.
     */
    public function execute(ApproveBetaRequestCommand $command): BetaRequestEntity
    {
        $request = $this->betaRequests->findById($command->betaRequestId);
        $request->approve();
        $request = $this->betaRequests->save($request);

        Notification::route('mail', $request->email)
            ->notify(new BetaRequestApproved($request));

        return $request;
    }
}
