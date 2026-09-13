<?php

declare(strict_types=1);

namespace App\Application\Actions\Presentations;

use App\Ai\Agents\DeckArchitect;
use App\Ai\DeckAssembler;
use App\Application\Commands\GenerateDeckFromPlanCommand;
use App\Domain\Presentation\Contracts\PresentationRepository;
use App\Domain\Presentation\Entities\PresentationEntity;

class GenerateDeckFromPlan
{
    public function __construct(
        private readonly PresentationRepository $presentations,
        private readonly DeckAssembler $assembler,
    ) {}

    public function execute(GenerateDeckFromPlanCommand $command): PresentationEntity
    {
        $structured = (new DeckArchitect)->prompt($command->plan)->toArray();

        $deck = $this->assembler->assemble($structured);

        $title = $structured['title'] ?? null;
        $name = $command->name !== ''
            ? $command->name
            : (is_string($title) && $title !== '' ? $title : 'Untitled deck');

        $presentation = new PresentationEntity(
            team_id: $command->team_id,
            name: $name,
            content: $deck['content'],
            flow: $deck['flow'],
        );

        return $this->presentations->save($presentation);
    }
}
