<?php

declare(strict_types=1);

use App\Application\Concerns\EmitsEvents;
use App\Application\Events\BetaRequestCreated;
use App\Domain\Beta\Entities\BetaRequestEntity;
use App\Enums\DomainEventType;
use Illuminate\Support\Facades\Event;

function makeEmitter(): object
{
    return new class
    {
        use EmitsEvents;
    };
}

test('emit wraps each recorded event of a type in the app event and dispatches it', function () {
    Event::fake();

    $entity = BetaRequestEntity::create('Ada Lovelace', 'ada@example.com');

    makeEmitter()->emit(BetaRequestCreated::class, $entity, DomainEventType::CREATED);

    Event::assertDispatched(
        BetaRequestCreated::class,
        fn (BetaRequestCreated $event): bool => $event->entity() === $entity,
    );
    Event::assertDispatchedTimes(BetaRequestCreated::class, 1);
});

test('emit dispatches nothing when the entity has no events of that type', function () {
    Event::fake();

    $entity = BetaRequestEntity::create('Ada Lovelace', 'ada@example.com');

    // The entity only recorded a CREATED event, so asking for UPDATED yields nothing.
    makeEmitter()->emit(BetaRequestCreated::class, $entity, DomainEventType::UPDATED);

    Event::assertNotDispatched(BetaRequestCreated::class);
});

test('emitAll dispatches each event as-is', function () {
    Event::fake();

    $entity = BetaRequestEntity::create('Ada Lovelace', 'ada@example.com');
    $events = [new BetaRequestCreated($entity->getCreatedEvents()[0])];

    makeEmitter()->emitAll($events);

    Event::assertDispatchedTimes(BetaRequestCreated::class, 1);
});
