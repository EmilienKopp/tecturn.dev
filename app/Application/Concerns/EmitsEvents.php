<?php

declare(strict_types=1);

namespace App\Application\Concerns;

use App\Domain\Contracts\Entity;
use App\Enums\DomainEventType;

trait EmitsEvents
{
    /**
     * Wrap each plain domain event of the given type in the supplied application
     * event class and dispatch it.
     *
     * @param  class-string  $eventClass
     */
    public function emit(string $eventClass, Entity $entity, DomainEventType $type): void
    {
        foreach ($entity->getEvents($type) as $event) {
            event(new $eventClass($event));
        }
    }

    /**
     * @param  iterable<object>  $events
     */
    public function emitAll(iterable $events): void
    {
        foreach ($events as $event) {
            event($event);
        }
    }
}
