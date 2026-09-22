<?php

namespace App\Domain\Contracts;

class DomainEvent
{
    public function __construct(
        protected Entity $entity
    ) {}

    public static function plain(Entity $entity): self
    {
        return new static($entity);
    }

    public function entity(): Entity
    {
        return $this->entity;
    }
}
