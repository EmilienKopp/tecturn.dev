<?php

declare(strict_types=1);

namespace App\Domain\Presentation\Contracts;

use App\Domain\Presentation\Entities\TalkEntity;

interface TalkRepository
{
    public function save(TalkEntity $talk): TalkEntity;

    public function findById(int $id): TalkEntity;

    /** The next free major version across all decks of the talk (1 when none). */
    public function nextMajorVersion(int $talkId): int;

    /** The next free minor version within the given major (0 when none exist). */
    public function nextMinorVersion(int $talkId, int $major): int;
}
