<?php

namespace App\Application\Contracts;

use App\Domain\Contracts\DomainEvent;
use App\Domain\Contracts\Entity;

abstract class AppEvent
{
    public function __construct(
        public DomainEvent $domainEvent,
    ) {}

    public function entity(): Entity
    {
        return $this->domainEvent->entity();
    }
}
