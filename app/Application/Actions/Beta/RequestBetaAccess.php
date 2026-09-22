<?php

declare(strict_types=1);

namespace App\Application\Actions\Beta;

use App\Application\Commands\RequestBetaAccessCommand;
use App\Application\Concerns\EmitsEvents;
use App\Application\Events\BetaRequestCreated;
use App\Domain\Beta\Contracts\BetaRequestRepository;
use App\Domain\Beta\Entities\BetaRequestEntity;
use App\Enums\DomainEventType;

class RequestBetaAccess
{
    use EmitsEvents;

    public function __construct(
        private readonly BetaRequestRepository $betaRequests,
    ) {}

    /**
     * Record a visitor's request to join the private beta and notify both the
     * admins (a new request came in) and the requester (we received it).
     */
    public function execute(RequestBetaAccessCommand $command): BetaRequestEntity
    {
        $entity = BetaRequestEntity::create(
            name: $command->name,
            email: $command->email,
            message: $command->message,
        );
        $saved = $this->betaRequests->save($entity);

        $this->emit(BetaRequestCreated::class, $entity, DomainEventType::CREATED);

        return $saved;
    }
}
