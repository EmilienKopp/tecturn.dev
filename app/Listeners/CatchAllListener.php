<?php

namespace App\Listeners;

use App\Domain\Contracts\DomainEvent;

class CatchAllListener
{
    public function handle(DomainEvent $event): void
    {
        // no-op until further notice
    }
}
