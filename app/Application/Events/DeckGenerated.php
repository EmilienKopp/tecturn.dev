<?php

declare(strict_types=1);

namespace App\Application\Events;

use App\Application\Contracts\AppEvent;
use App\Domain\Contracts\DomainEvent;
use App\Domain\Presentation\Entities\PresentationEntity;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Broadcast to the generating user once their deck has finished building, so the
 * frontend can toast them with a link to open it.
 */
class DeckGenerated extends AppEvent implements ShouldBroadcastNow
{
    public function __construct(
        DomainEvent $domainEvent,
        public int $userId,
        public string $teamSlug,
    ) {
        parent::__construct($domainEvent);
    }

    /**
     * @return PrivateChannel[]
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("App.Models.User.{$this->userId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'deck.generated';
    }

    /**
     * @return array{id: int|null, name: string, url: string}
     */
    public function broadcastWith(): array
    {
        /** @var PresentationEntity $presentation */
        $presentation = $this->entity();

        return [
            'id' => $presentation->id,
            'name' => $presentation->name,
            'url' => route('presentations.edit', [
                'current_team' => $this->teamSlug,
                'presentation' => $presentation->id,
            ]),
        ];
    }
}
