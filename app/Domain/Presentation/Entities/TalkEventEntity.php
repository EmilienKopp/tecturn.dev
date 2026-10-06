<?php

declare(strict_types=1);

namespace App\Domain\Presentation\Entities;

use App\Domain\BaseEntity;
use App\Domain\Presentation\Exceptions\InvalidTalkEvent;
use DateTimeInterface;

class TalkEventEntity extends BaseEntity
{
    /**
     * A calendar occasion: a date (with an optional start time) a talk is
     * scheduled for. Giving the same talk twice in one day means two events.
     */
    public function __construct(
        public int $team_id,
        public string $name,
        public DateTimeInterface $date,
        public ?string $start_time = null,
        public ?int $talk_id = null,
        public ?int $id = null,
    ) {
        if (trim($this->name) === '' || mb_strlen($this->name) > 255) {
            throw new InvalidTalkEvent('Event name must be between 1 and 255 characters.');
        }
    }

    public function reschedule(DateTimeInterface $date, ?string $startTime): void
    {
        $this->date = $date;
        $this->start_time = $startTime;
    }

    public function rename(string $name): void
    {
        if (trim($name) === '' || mb_strlen($name) > 255) {
            throw new InvalidTalkEvent('Event name must be between 1 and 255 characters.');
        }

        $this->name = $name;
    }

    public function attachTalk(?int $talkId): void
    {
        $this->talk_id = $talkId;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'talk_id' => $this->talk_id,
            'name' => $this->name,
            'date' => $this->date,
            'start_time' => $this->start_time,
        ];
    }
}
