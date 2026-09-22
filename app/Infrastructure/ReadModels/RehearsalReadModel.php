<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModels;

use App\Models\Views\RehearsalHistoryView;

class RehearsalReadModel
{
    /**
     * A team's rehearsal history, most recent first, without the heavy deck
     * snapshots — those load one at a time on the replay page.
     *
     * @return array<int, array{
     *     id: int,
     *     presentation_id: int,
     *     presentation_name: string,
     *     started_at: string,
     *     duration_seconds: int,
     *     slide_count: int,
     *     slide_timings: list<array{slide: int, seconds: int}>
     * }>
     */
    public function listForTeam(int $teamId): array
    {
        return RehearsalHistoryView::query()
            ->where('team_id', $teamId)
            ->orderByDesc('started_at')
            ->get()
            ->map(fn (RehearsalHistoryView $run): array => [
                'id' => $run->id,
                'presentation_id' => $run->presentation_id,
                'presentation_name' => $run->presentation_name,
                'started_at' => $run->started_at->toISOString(),
                'duration_seconds' => $run->duration_seconds,
                'slide_count' => count($run->content['slides'] ?? []),
                'slide_timings' => $run->slideTimings(),
            ])
            ->all();
    }

    /**
     * A single rehearsal with its frozen deck, ready for read-only replay.
     *
     * @return array{
     *     id: int,
     *     presentation_id: int,
     *     presentation_name: string,
     *     started_at: string,
     *     ended_at: string,
     *     duration_seconds: int,
     *     slide_timings: list<array{slide: int, seconds: int}>,
     *     step_events: list<array{at_ms: int, slide: int, step: int}>,
     *     has_recording: bool,
     *     content: array<string, mixed>,
     *     flow: array<string, mixed>|null
     * }
     */
    public function findForReplay(int $runId): array
    {
        $run = RehearsalHistoryView::query()->findOrFail($runId);

        return [
            'id' => $run->id,
            'presentation_id' => $run->presentation_id,
            'presentation_name' => $run->presentation_name,
            'started_at' => $run->started_at->toISOString(),
            'ended_at' => $run->ended_at->toISOString(),
            'duration_seconds' => $run->duration_seconds,
            'slide_timings' => $run->slideTimings(),
            'step_events' => $run->stepEvents(),
            'has_recording' => $run->has_recording,
            'content' => $run->content,
            'flow' => $run->flow,
        ];
    }
}
