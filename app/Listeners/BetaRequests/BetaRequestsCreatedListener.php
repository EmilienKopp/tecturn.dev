<?php

namespace App\Listeners\BetaRequests;

use App\Application\Events\BetaRequestCreated;
use App\Domain\Beta\Entities\BetaRequestEntity;
use App\Notifications\Beta\BetaRequestReceived;
use App\Notifications\Beta\BetaRequestSubmitted;
use Illuminate\Support\Facades\Notification;

class BetaRequestsCreatedListener
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(BetaRequestCreated $event): void
    {
        $request = $event->entity();

        if (! $request instanceof BetaRequestEntity) {
            return;
        }

        $admins = config('admin.emails', []);

        logger()->info('Beta request created', ['request' => $request]);

        if ($admins !== []) {
            Notification::route('mail', $admins)
                ->notify(new BetaRequestSubmitted($request));
        }

        Notification::route('mail', $request->email)
            ->notify(new BetaRequestReceived($request));
    }
}
