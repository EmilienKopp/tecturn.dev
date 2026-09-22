<?php

declare(strict_types=1);

namespace App\Application\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Broadcast to the generating user when their deck build failed, so the frontend
 * can toast an error instead of leaving them waiting.
 */
class DeckGenerationFailed implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public int $userId,
    ) {}

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
        return 'deck.generation.failed';
    }
}
