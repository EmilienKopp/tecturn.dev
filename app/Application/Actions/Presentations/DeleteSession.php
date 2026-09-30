<?php

declare(strict_types=1);

namespace App\Application\Actions\Presentations;

use App\Application\Commands\DeleteSessionCommand;
use App\Domain\Presentation\Contracts\PresentationSessionRepository;

class DeleteSession
{
    public function __construct(
        private readonly PresentationSessionRepository $sessions,
    ) {}

    /**
     * Drops a session and everything it recorded (reactions, viewers, word
     * count) — for runs that went live by accident. Irreversible on purpose:
     * the deleted numbers stop feeding engagement and delivery stats.
     */
    public function execute(DeleteSessionCommand $command): void
    {
        $this->sessions->deleteById($command->sessionId);
    }
}
