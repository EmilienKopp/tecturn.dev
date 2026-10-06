<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModels;

use App\Models\Views\CalendarEventView;

class TalkEventReadModel
{
    /**
     * Every scheduled event for the team, soonest first. The calendar page
     * filters client-side per displayed month, so no date window is applied.
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     date: string,
     *     start_time: string|null,
     *     talk_id: int|null,
     *     talk_title: string|null
     * }>
     */
    public function listForTeam(int $teamId): array
    {
        return CalendarEventView::query()
            ->where('team_id', $teamId)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (CalendarEventView $event): array => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->date->toDateString(),
                'start_time' => $event->start_time !== null
                    ? substr($event->start_time, 0, 5)
                    : null,
                'talk_id' => $event->talk_id,
                'talk_title' => $event->talk_title,
            ])
            ->all();
    }
}
