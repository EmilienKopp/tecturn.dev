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
     * @var SplObjectStorage<DomainEventType,list<DomainEvent>>
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

    public static function fromArray(array $data): static
    {
        $data = array_filter(
            $data,
            static fn (string $key): bool => property_exists(static::class, $key),
            ARRAY_FILTER_USE_KEY,
        );

        return new static(...$data);
    }

    final public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    public function events(): array
    {
        $flattened = [];
        if ($this->events !== null) {
            foreach ($this->events as $type) {
                $flattened = [...$flattened, ...($this->events[$type] ?? [])];
            }
        }

        return $flattened;
    }

    /**
     * @return list<DomainEvent>
     */
    public function getEvents(DomainEventType $type): array
    {
        $this->ensureEventStorage();

        return $this->events[$type] ?? [];
    }

    public function getCreatedEvents(): array
    {
        return $this->getEvents(DomainEventType::CREATED);
    }

    public function getUpdatedEvents(): array
    {
        return $this->getEvents(DomainEventType::UPDATED);
    }

    public function getDeletedEvents(): array
    {
        return $this->getEvents(DomainEventType::DELETED);
    }

    public function getRestoredEvents(): array
    {
        return $this->getEvents(DomainEventType::RESTORED);
    }

    public function created(): array
    {
        return [DomainEvent::plain($this)];
    }

    public function updated(): array
    {
        return [DomainEvent::plain($this)];
    }

    public function deleted(): array
    {
        return [DomainEvent::plain($this)];
    }

    public function restored(): array
    {
        return [DomainEvent::plain($this)];
    }

    public function onCreated()
    {
        $this->ensureEventStorage();
        $this->events[DomainEventType::CREATED] = $this->created();
    }

    public function onUpdated()
    {
        $this->ensureEventStorage();
        $this->events[DomainEventType::UPDATED] = $this->updated();
    }

    public function onDeleted()
    {
        $this->ensureEventStorage();
        $this->events[DomainEventType::DELETED] = $this->deleted();
    }

    public function onRestored()
    {
        $this->ensureEventStorage();
        $this->events[DomainEventType::RESTORED] = $this->restored();
    }

    public function flushEvents(): array
    {
        $events = $this->events();
        $this->events = null;

        return $events;
    }

    /**
     * @param  list<DomainEvent>  $events
     */
    public function on(DomainEventType $type, array $events)
    {
        $this->ensureEventStorage();
        $this->events[$type] = $events;
    }

    private function ensureEventStorage(): void
    {
        if ($this->events === null) {
            $this->events = new SplObjectStorage;
        }
    }
}
