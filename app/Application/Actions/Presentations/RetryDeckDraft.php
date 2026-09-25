<?php

declare(strict_types=1);

namespace App\Application\Actions\Presentations;

use App\Application\Commands\RetryDeckDraftCommand;
use App\Domain\Presentation\Contracts\PresentationRepository;
use App\Domain\Presentation\Entities\PresentationEntity;
use RuntimeException;

/**
 * Resets a failed draft back to "requested" so its stored plan can be rebuilt.
 * The caller re-dispatches the generation job with the returned entity.
 */
class RetryDeckDraft
{
    public function __construct(
        private readonly PresentationRepository $presentations,
    ) {}

    public function execute(RetryDeckDraftCommand $command): PresentationEntity
    {
        $presentation = $this->presentations->findById($command->presentation_id);

        $plan = $presentation->draftPlan;

        if ($plan === null || $plan === '') {
            throw new RuntimeException("Presentation {$command->presentation_id} has no draft plan to retry.");
        }

        $presentation->markDraftRequested(now()->toDateTimeImmutable(), $plan);

        return $this->presentations->save($presentation);
    }
}
