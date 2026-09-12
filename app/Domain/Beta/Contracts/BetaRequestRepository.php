<?php

declare(strict_types=1);

namespace App\Domain\Beta\Contracts;

use App\Domain\Beta\Entities\BetaRequestEntity;

interface BetaRequestRepository
{
    /**
     * Persist a beta access request. Repeated requests for the same email
     * update the existing record rather than creating a duplicate.
     */
    public function save(BetaRequestEntity $request): BetaRequestEntity;

    public function findById(int $id): BetaRequestEntity;
}
