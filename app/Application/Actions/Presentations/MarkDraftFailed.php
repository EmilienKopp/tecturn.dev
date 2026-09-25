<?php

declare(strict_types=1);

namespace App\Application\Actions\Presentations;

use App\Application\Commands\MarkDraftFailedCommand;
use App\Domain\Presentation\Contracts\PresentationRepository;

/**
 * Records that an AI draft build failed, keeping the row so the user can see the
 * error on the index and retry.
 */
class MarkDraftFailed
{
    public function __construct(
        private readonly PresentationRepository $presentations,
    ) {}

    public function execute(MarkDraftFailedCommand $command): void
    {
        $presentation = $this->presentations->findById($command->presentation_id);

        $presentation->markDraftFailed(now()->toDateTimeImmutable(), $command->error);

        $this->presentations->save($presentation);
    }
}
