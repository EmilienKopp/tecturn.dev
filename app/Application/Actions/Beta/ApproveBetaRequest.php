<?php

declare(strict_types=1);

namespace App\Application\Actions\Beta;

use App\Application\Commands\ApproveBetaRequestCommand;
use App\Domain\Beta\Contracts\BetaInvitationGateway;
use App\Domain\Beta\Contracts\BetaRequestRepository;
use App\Domain\Beta\Entities\BetaRequestEntity;
use App\Notifications\Beta\BetaRequestApproved;
use Illuminate\Support\Facades\Notification;

class ApproveBetaRequest
{
    public function __construct(
        private readonly BetaRequestRepository $betaRequests,
        private readonly BetaInvitationGateway $invitations,
    ) {}

    /**
     * Approve a pending beta request: provision the requester's access by
     * sending them an invitation, then mark the request approved and let them
     * know they're in. The invitation is sent before the status flips so a
     * provisioning failure leaves the request pending rather than approved
     * without access.
     */
    public function execute(ApproveBetaRequestCommand $command): BetaRequestEntity
    {
        $request = $this->betaRequests->findById($command->betaRequestId);

        $this->invitations->invite($request->email);

        $request->approve();
        $request = $this->betaRequests->save($request);

        Notification::route('mail', $request->email)
            ->notify(new BetaRequestApproved($request));

        return $request;
    }
}
