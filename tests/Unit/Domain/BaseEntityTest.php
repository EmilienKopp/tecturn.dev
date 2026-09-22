<?php

declare(strict_types=1);

use App\Domain\BaseEntity;
use App\Domain\Contracts\DomainEvent;
use App\Enums\DomainEventType;

/**
 * Concrete stand-in so we can exercise BaseEntity's event machinery without
 * depending on any real domain entity's rules.
 */
class EventfulStubEntity extends BaseEntity
{
    /** @var list<DomainEvent> */
    public array $onCreate = [];

    /** @var list<DomainEvent> */
    public array $onUpdate = [];

    /** @var list<DomainEvent> */
    public array $onDelete = [];

    /** @var list<DomainEvent> */
    public array $onRestore = [];

    public function created(): array
    {
        return $this->onCreate;
    }

    public function updated(): array
    {
        return $this->onUpdate;
    }

    public function deleted(): array
    {
        return $this->onDelete;
    }

    public function restored(): array
    {
        return $this->onRestore;
    }
}

class StubDomainEvent extends DomainEvent {}

function makeStubEvent(): StubDomainEvent
{
    return new StubDomainEvent(new EventfulStubEntity);
}

it('starts with no recorded events', function () {
    $entity = new EventfulStubEntity;

    expect($entity->events())->toBe([])
        ->and($entity->getCreatedEvents())->toBe([])
        ->and($entity->getUpdatedEvents())->toBe([])
        ->and($entity->getDeletedEvents())->toBe([])
        ->and($entity->getRestoredEvents())->toBe([]);
});

it('records created events when onCreated is called', function () {
    $event = makeStubEvent();
    $entity = new EventfulStubEntity;
    $entity->onCreate = [$event];

    $entity->onCreated();

    expect($entity->getCreatedEvents())->toBe([$event])
        ->and($entity->events())->toBe([$event])
        ->and($entity->getUpdatedEvents())->toBe([]);
});

it('keeps each lifecycle bucket separate', function () {
    $created = makeStubEvent();
    $updated = makeStubEvent();
    $deleted = makeStubEvent();
    $restored = makeStubEvent();

    $entity = new EventfulStubEntity;
    $entity->onCreate = [$created];
    $entity->onUpdate = [$updated];
    $entity->onDelete = [$deleted];
    $entity->onRestore = [$restored];

    $entity->onCreated();
    $entity->onUpdated();
    $entity->onDeleted();
    $entity->onRestored();

    expect($entity->getCreatedEvents())->toBe([$created])
        ->and($entity->getUpdatedEvents())->toBe([$updated])
        ->and($entity->getDeletedEvents())->toBe([$deleted])
        ->and($entity->getRestoredEvents())->toBe([$restored]);
});

it('flattens every bucket in events()', function () {
    $created = makeStubEvent();
    $updatedOne = makeStubEvent();
    $updatedTwo = makeStubEvent();

    $entity = new EventfulStubEntity;
    $entity->onCreate = [$created];
    $entity->onUpdate = [$updatedOne, $updatedTwo];

    $entity->onCreated();
    $entity->onUpdated();

    expect($entity->events())
        ->toHaveCount(3)
        ->toContain($created, $updatedOne, $updatedTwo);
});

it('does not collide when the same enum case keys the storage twice', function () {
    $first = makeStubEvent();
    $second = makeStubEvent();

    $entity = new EventfulStubEntity;
    $entity->onUpdate = [$first];
    $entity->onUpdated();

    // Re-running the same lifecycle hook replaces, rather than appends.
    $entity->onUpdate = [$second];
    $entity->onUpdated();

    expect($entity->getUpdatedEvents())->toBe([$second])
        ->and($entity->events())->toBe([$second]);
});

it('flushes events and resets the storage', function () {
    $event = makeStubEvent();
    $entity = new EventfulStubEntity;
    $entity->onCreate = [$event];
    $entity->onCreated();

    $flushed = $entity->flushEvents();

    expect($flushed)->toBe([$event])
        ->and($entity->events())->toBe([])
        ->and($entity->getCreatedEvents())->toBe([]);
});

it('records fresh events after a flush', function () {
    $entity = new EventfulStubEntity;
    $entity->onCreate = [makeStubEvent()];
    $entity->onCreated();
    $entity->flushEvents();

    $next = makeStubEvent();
    $entity->onUpdate = [$next];
    $entity->onUpdated();

    expect($entity->events())->toBe([$next]);
});

it('sets a bucket directly with on()', function () {
    $event = makeStubEvent();
    $entity = new EventfulStubEntity;

    $entity->on(DomainEventType::DELETED, [$event]);

    expect($entity->getDeletedEvents())->toBe([$event])
        ->and($entity->events())->toBe([$event]);
});
