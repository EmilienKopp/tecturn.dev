<?php

declare(strict_types=1);

use App\Domain\Beta\Entities\BetaRequestEntity;
use App\Domain\Contracts\DomainEvent;
use App\Enums\BetaRequestStatus;

it('records a plain created event pointing at the entity', function () {
    $entity = BetaRequestEntity::create('Ada Lovelace', 'ada@example.com');

    expect($entity->getCreatedEvents())->toHaveCount(1)
        ->and($entity->getCreatedEvents()[0])->toBeInstanceOf(DomainEvent::class)
        ->and($entity->getCreatedEvents()[0]->entity())->toBe($entity)
        ->and($entity->events())->toHaveCount(1);
});

it('records an updated event when approved', function () {
    $entity = BetaRequestEntity::create('Ada Lovelace', 'ada@example.com');
    $entity->flushEvents();

    $entity->approve();

    expect($entity->status)->toBe(BetaRequestStatus::Approved)
        ->and($entity->getUpdatedEvents())->toHaveCount(1)
        ->and($entity->getUpdatedEvents()[0]->entity())->toBe($entity);
});

it('records an updated event when rejected', function () {
    $entity = BetaRequestEntity::create('Ada Lovelace', 'ada@example.com');
    $entity->flushEvents();

    $entity->reject();

    expect($entity->status)->toBe(BetaRequestStatus::Rejected)
        ->and($entity->getUpdatedEvents())->toHaveCount(1);
});

it('drains queued events on flush and does not replay them', function () {
    $entity = BetaRequestEntity::create('Ada Lovelace', 'ada@example.com');

    $flushed = $entity->flushEvents();

    expect($flushed)->toHaveCount(1)
        ->and($flushed[0])->toBeInstanceOf(DomainEvent::class)
        ->and($entity->flushEvents())->toBe([]);
});
