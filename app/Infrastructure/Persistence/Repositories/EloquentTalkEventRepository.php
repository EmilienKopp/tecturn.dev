<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Presentation\Contracts\TalkEventRepository;
use App\Domain\Presentation\Entities\TalkEventEntity;
use App\Models\PresentationSession;
use App\Models\TalkEvent;

class EloquentTalkEventRepository implements TalkEventRepository
{
    public function save(TalkEventEntity $event): TalkEventEntity
    {
        $attributes = [
            'team_id' => $event->team_id,
            'talk_id' => $event->talk_id,
            'name' => $event->name,
            'date' => $event->date,
            'start_time' => $event->start_time,
        ];

        if ($event->id === null) {
            $model = TalkEvent::create($attributes);
        } else {
            $model = TalkEvent::findOrFail($event->id);
            $model->update($attributes);
        }

        return $model->refresh()->toEntity();
    }

    public function findById(int $id): TalkEventEntity
    {
        return TalkEvent::findOrFail($id)->toEntity();
    }

    public function deleteById(int $id): void
    {
        // presentation_sessions.talk_event_id has no DB-level FK (sqlite view
        // constraints), so detach sessions explicitly before deleting.
        PresentationSession::query()
            ->where('talk_event_id', $id)
            ->update(['talk_event_id' => null]);

        TalkEvent::query()->whereKey($id)->delete();
    }
}
