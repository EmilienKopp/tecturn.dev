<?php

declare(strict_types=1);

namespace App\Application\Actions\Presentations;

use App\Application\Commands\CreateDeckFromSnapshotCommand;
use App\Domain\Presentation\Contracts\PresentationRepository;
use App\Domain\Presentation\Contracts\RehearsalRepository;
use App\Domain\Presentation\Contracts\TalkRepository;
use App\Domain\Presentation\Entities\PresentationEntity;
use App\Domain\Presentation\Entities\TalkEntity;
use App\Domain\Presentation\ValueObjects\FlowGraph;
use App\Domain\Presentation\ValueObjects\PresentationContent;

class CreateDeckFromSnapshot
{
    public function __construct(
        private readonly PresentationRepository $presentations,
        private readonly RehearsalRepository $rehearsals,
        private readonly TalkRepository $talks,
    ) {}

    /**
     * Materializes a rehearsal's frozen deck as a new deck, versioned as the
     * next major of the source deck's talk. The talk is created lazily: if the
     * source deck is unversioned it becomes 1.0 and the snapshot deck 2.0.
     */
    public function execute(CreateDeckFromSnapshotCommand $command): PresentationEntity
    {
        $run = $this->rehearsals->findById($command->rehearsalId);
        $source = $this->presentations->findById($run->presentation_id);
        $talkId = $this->ensureTalk($source);

        $copy = new PresentationEntity(
            team_id: $source->team_id,
            name: $source->name,
            content: PresentationContent::fromArray($run->content),
            isPrivate: $source->isPrivate,
            talkSettings: $source->talkSettings,
            flow: $run->flow !== null ? FlowGraph::fromArray($run->flow) : null,
            source: $source->source,
        );
        $copy->assignVersion($talkId, $this->talks->nextMajorVersion($talkId), 0);

        return $this->presentations->save($copy);
    }

    /** Returns the deck's talk id, creating the talk and promoting the deck to 1.0 when it has none. */
    private function ensureTalk(PresentationEntity $deck): int
    {
        if ($deck->talk_id !== null) {
            return $deck->talk_id;
        }

        $talk = $this->talks->save(new TalkEntity(
            team_id: $deck->team_id,
            title: $deck->name,
        ));

        $deck->assignVersion($talk->id, 1, 0);
        $this->presentations->save($deck);

        return $talk->id;
    }
}
