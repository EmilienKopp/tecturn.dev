<?php

declare(strict_types=1);

namespace App\Application\Actions\Presentations;

use App\Application\Commands\RequestDeckDraftCommand;
use App\Domain\Presentation\Contracts\PresentationRepository;
use App\Domain\Presentation\Entities\PresentationEntity;
use App\Domain\Presentation\ValueObjects\PresentationContent;

/**
 * Persists a placeholder deck row before the slow AI build runs, so the draft
 * is inspectable from the start and the index can render it as a skeleton.
 */
class RequestDeckDraft
{
    /** Shown on the index skeleton until the AI provides a real title. */
    public const string GENERATING_NAME = 'Generating deck…';

    public function __construct(
        private readonly PresentationRepository $presentations,
    ) {}

    public function execute(RequestDeckDraftCommand $command): PresentationEntity
    {
        $presentation = new PresentationEntity(
            team_id: $command->team_id,
            name: $command->name !== '' ? $command->name : self::GENERATING_NAME,
            content: PresentationContent::empty($command->background),
        );

        $presentation->markDraftRequested(now()->toDateTimeImmutable(), $command->plan);

        return $this->presentations->save($presentation);
    }
}
