<?php

declare(strict_types=1);

namespace App\Application\Actions\Beta;

use App\Application\Commands\RequestBetaAccessCommand;
use App\Domain\Beta\Contracts\BetaRequestRepository;
use App\Domain\Beta\Entities\BetaRequestEntity;
use App\Notifications\Beta\BetaRequestReceived;
use App\Notifications\Beta\BetaRequestSubmitted;
use Illuminate\Support\Facades\Notification;

class RequestBetaAccess
{
    public function __construct(
        private readonly BetaRequestRepository $betaRequests,
    ) {}

    /**
     * Record a visitor's request to join the private beta and notify both the
     * admins (a new request came in) and the requester (we received it).
     */
    public function execute(RequestBetaAccessCommand $command): BetaRequestEntity
    {
        $request = $this->betaRequests->save(new BetaRequestEntity(
            name: $command->name,
            email: $command->email,
            message: $command->message,
        ));

        $admins = config('admin.emails', []);

        if ($admins !== []) {
            Notification::route('mail', $admins)
                ->notify(new BetaRequestSubmitted($request));
        }

        Notification::route('mail', $request->email)
            ->notify(new BetaRequestReceived($request));

        return $request;
    }
}
