<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Presentation\Contracts\TalkRepository;
use App\Domain\Presentation\Entities\TalkEntity;
use App\Models\Presentation;
use App\Models\Talk;

class EloquentTalkRepository implements TalkRepository
{
    public function save(TalkEntity $talk): TalkEntity
    {
        $attributes = [
            'team_id' => $talk->team_id,
            'title' => $talk->title,
        ];

        if ($talk->id === null) {
            $model = Talk::create($attributes);
        } else {
            $model = Talk::findOrFail($talk->id);
            $model->update($attributes);
        }

        return $model->refresh()->toEntity();
    }

    public function findById(int $id): TalkEntity
    {
        return Talk::findOrFail($id)->toEntity();
    }

    public function nextMajorVersion(int $talkId): int
    {
        $max = Presentation::query()
            ->where('talk_id', $talkId)
            ->max('version_major');

        return $max === null ? 1 : ((int) $max) + 1;
    }

    public function nextMinorVersion(int $talkId, int $major): int
    {
        $max = Presentation::query()
            ->where('talk_id', $talkId)
            ->where('version_major', $major)
            ->max('version_minor');

        return $max === null ? 0 : ((int) $max) + 1;
    }
}
