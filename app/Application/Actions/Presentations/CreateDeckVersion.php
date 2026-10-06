<?php

declare(strict_types=1);

namespace App\Application\Actions\Presentations;

use App\Application\Commands\CreateDeckVersionCommand;
use App\Domain\Presentation\Contracts\PresentationRepository;
use App\Domain\Presentation\Contracts\TalkRepository;
use App\Domain\Presentation\Entities\PresentationEntity;
use App\Domain\Presentation\Entities\TalkEntity;

class CreateDeckVersion
{
    public function __construct(
        private readonly PresentationRepository $presentations,
        private readonly TalkRepository $talks,
    ) {}

    /**
     * Duplicates the deck as the next major or minor version of its talk.
     * The talk is created lazily: the first version action promotes the
     * source deck to 1.0 of a new talk named after it.
     */
    public function execute(CreateDeckVersionCommand $command): PresentationEntity
    {
        $source = $this->presentations->findById($command->presentationId);
        $talkId = $this->ensureTalk($source);

        if ($command->bump === 'major') {
            $major = $this->talks->nextMajorVersion($talkId);
            $minor = 0;
        } else {
            $major = $source->version_major ?? 1;
            $minor = $this->talks->nextMinorVersion($talkId, $major);
        }

        $copy = new PresentationEntity(
            team_id: $source->team_id,
            name: $source->name,
            content: $source->content,
            isPrivate: $source->isPrivate,
            talkSettings: $source->talkSettings,
            flow: $source->flow,
            source: $source->source,
        );
        $copy->assignVersion($talkId, $major, $minor);

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
