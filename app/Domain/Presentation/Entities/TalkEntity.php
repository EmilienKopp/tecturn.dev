<?php

declare(strict_types=1);

namespace App\Domain\Presentation\Entities;

use App\Domain\BaseEntity;

class TalkEntity extends BaseEntity
{
    /**
     * The abstract talk a deck delivers. Presentations attach to a talk as
     * versions (major.minor) of the same material.
     */
    public function __construct(
        public int $team_id,
        public string $title,
        public ?int $id = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'title' => $this->title,
        ];
    }
}
