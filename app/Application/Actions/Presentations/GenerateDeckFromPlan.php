<?php

declare(strict_types=1);

namespace App\Application\Actions\Presentations;

use App\Ai\Agents\Deckster;
use App\Ai\DeckAssembler;
use App\Application\Commands\GenerateDeckFromPlanCommand;
use App\Domain\Presentation\Contracts\PresentationRepository;
use App\Domain\Presentation\Entities\PresentationEntity;
use RuntimeException;

/**
 * Fills a requested draft with an AI-built deck: runs Deckster against the
 * draft's stored plan, assembles the result, and marks the draft complete.
 */
class GenerateDeckFromPlan
{
    public function __construct(
        private readonly PresentationRepository $presentations,
        private readonly DeckAssembler $assembler,
    ) {}

    public function execute(GenerateDeckFromPlanCommand $command): PresentationEntity
    {
        $presentation = $this->presentations->findById($command->presentation_id);

        $plan = $presentation->draftPlan;

        if ($plan === null || $plan === '') {
            throw new RuntimeException("Presentation {$command->presentation_id} has no draft plan to build from.");
        }

        $structured = (new Deckster)->prompt($plan)->toArray();

        $deck = $this->assembler->assemble($structured, $command->branding);

        $title = $structured['title'] ?? null;
        $name = $command->name !== ''
            ? $command->name
            : (is_string($title) && $title !== '' ? $title : 'Untitled deck');

        $presentation->rename($name);
        $presentation->replaceContent($deck['content']);
        $presentation->replaceFlow($deck['flow']);
        $presentation->markDraftCompleted(now()->toDateTimeImmutable());

        $saved = $this->presentations->save($presentation);
        $saved->onCreated();

        return $saved;
    }
}
