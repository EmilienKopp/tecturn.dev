<?php

declare(strict_types=1);

namespace App\Application\Actions\Beta;

use App\Application\Commands\RejectBetaRequestCommand;
use App\Domain\Beta\Contracts\BetaRequestRepository;
use App\Domain\Beta\Entities\BetaRequestEntity;

class RejectBetaRequest
{
    public function __construct(
        private readonly BetaRequestRepository $betaRequests,
    ) {}

    /**
     * Reject a beta request. No email is sent to the requester.
     */
    public function execute(RejectBetaRequestCommand $command): BetaRequestEntity
    {
        $request = $this->betaRequests->findById($command->betaRequestId);
        $request->reject();

        return $this->betaRequests->save($request);
    }
}
