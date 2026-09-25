<?php

declare(strict_types=1);

namespace App\Domain;

use App\Concerns\ArrayLike;
use App\Domain\Contracts\DomainEvent;
use App\Domain\Contracts\Entity;
use App\Enums\DomainEventType;
use ArrayIterator;
use SplObjectStorage;
use Traversable;

abstract class BaseEntity implements Entity
{
    use ArrayLike;

    /**
     * @var SplObjectStorage<DomainEventType,list<DomainEvent>>|null
     */
    protected ?SplObjectStorage $events = null;

    public function __construct(mixed ...$data)
    {
        foreach (array_keys(get_object_vars($this)) as $property) {
            if (array_key_exists($property, $data)) {
                $this->{$property} = $data[$property];
            }
        }
    }

    final public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * @return list<DomainEvent>
     */
    public function events(): array
    {
        $flattened = [];
        if ($this->events !== null) {
            foreach ($this->events as $type) {
                $flattened = [...$flattened, ...$this->events[$type]];
            }
        }

        return $flattened;
    }

    /**
     * @return list<DomainEvent>
     */
    public function getEvents(DomainEventType $type): array
    {
        $storage = $this->ensureEventStorage();

        return $storage[$type] ?? [];
    }

    /**
     * @return list<DomainEvent>
     */
    public function getCreatedEvents(): array
    {
        return $this->getEvents(DomainEventType::CREATED);
    }

    /**
     * @return list<DomainEvent>
     */
    public function getUpdatedEvents(): array
    {
        return $this->getEvents(DomainEventType::UPDATED);
    }

    /**
     * @return list<DomainEvent>
     */
    public function getDeletedEvents(): array
    {
        return $this->getEvents(DomainEventType::DELETED);
    }

    /**
     * @return list<DomainEvent>
     */
    public function getRestoredEvents(): array
    {
        return $this->getEvents(DomainEventType::RESTORED);
    }

    /**
     * @return list<DomainEvent>
     */
    public function created(): array
    {
        return [DomainEvent::plain($this)];
    }

    /**
     * @return list<DomainEvent>
     */
    public function updated(): array
    {
        return [DomainEvent::plain($this)];
    }

    /**
     * @return list<DomainEvent>
     */
    public function deleted(): array
    {
        return [DomainEvent::plain($this)];
    }

    /**
     * @return list<DomainEvent>
     */
    public function restored(): array
    {
        return [DomainEvent::plain($this)];
    }

    public function onCreated(): void
    {
        $this->ensureEventStorage()[DomainEventType::CREATED] = $this->created();
    }

    public function onUpdated(): void
    {
        $this->ensureEventStorage()[DomainEventType::UPDATED] = $this->updated();
    }

    public function onDeleted(): void
    {
        $this->ensureEventStorage()[DomainEventType::DELETED] = $this->deleted();
    }

    public function onRestored(): void
    {
        $this->ensureEventStorage()[DomainEventType::RESTORED] = $this->restored();
    }

    /**
     * @return list<DomainEvent>
     */
    public function flushEvents(): array
    {
        $events = $this->events();
        $this->events = null;

        return $events;
    }

    /**
     * @param  list<DomainEvent>  $events
     */
    public function on(DomainEventType $type, array $events): void
    {
        $this->ensureEventStorage()[$type] = $events;
    }

    /**
     * @return SplObjectStorage<DomainEventType, list<DomainEvent>>
     */
    private function ensureEventStorage(): SplObjectStorage
    {
        return $this->events ??= new SplObjectStorage;
    }
}
