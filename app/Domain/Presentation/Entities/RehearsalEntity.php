<?php

declare(strict_types=1);

namespace App\Domain\Presentation\Entities;

use App\Domain\BaseEntity;
use DateTimeInterface;

class RehearsalEntity extends BaseEntity
{
    /**
     * A finished rehearsal. Content and flow are frozen copies of the deck at
     * the moment the run was recorded, not references to the live deck.
     *
     * @param  list<array{slide: int, seconds: int}>  $slide_timings
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>|null  $flow
     * @param  list<array{at_ms: int, slide: int, step: int}>|null  $step_events
     */
    public function __construct(
        public int $presentation_id,
        public int $team_id,
        public DateTimeInterface $started_at,
        public DateTimeInterface $ended_at,
        public int $duration_seconds,
        public array $slide_timings,
        public array $content,
        public ?array $flow = null,
        public ?array $step_events = null,
        public ?int $id = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'presentation_id' => $this->presentation_id,
            'team_id' => $this->team_id,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'duration_seconds' => $this->duration_seconds,
            'slide_timings' => $this->slide_timings,
            'content' => $this->content,
            'flow' => $this->flow,
            'step_events' => $this->step_events,
        ];
    }
}
