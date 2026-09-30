<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModels;

use App\Models\Views\RehearsalHistoryView;
use App\Models\Views\SessionAnalyticsView;

class SessionReadModel
{
    /**
     * One live session in full, plus the rehearsal baseline of the same deck
     * so the detail page can compare live per-slide time against practice.
     *
     * @return array{
     *     session: array<string, mixed>,
     *     rehearsedSeconds: array<int, int>,
     *     rehearsalCount: int
     * }|null
     */
    public function detail(int $sessionId): ?array
    {
        $session = SessionAnalyticsView::query()->find($sessionId);

        if ($session === null) {
            return null;
        }

        [$rehearsedSeconds, $rehearsalCount] = $this->rehearsedSecondsPerSlide($session->presentation_id);

        return [
            'session' => [
                'id' => $session->id,
                'presentation_id' => $session->presentation_id,
                'presentation_name' => $session->presentation_name,
                'started_at' => $session->started_at->toISOString(),
                'ended_at' => $session->ended_at?->toISOString(),
                'is_live' => $session->ended_at === null,
                'duration_seconds' => $session->ended_at !== null
                    ? (int) $session->started_at->diffInSeconds($session->ended_at, true)
                    : (int) $session->started_at->diffInSeconds(now(), true),
                'viewer_count' => $session->viewer_count,
                'reaction_total' => $session->reaction_total,
                'reaction_counts' => $session->reaction_counts ?? [],
                'word_count' => $session->word_count,
                'slide_timings' => $session->slide_timings ?? [],
                'reaction_slides' => $session->reaction_slides ?? [],
            ],
            'rehearsedSeconds' => $rehearsedSeconds,
            'rehearsalCount' => $rehearsalCount,
        ];
    }

    /**
     * Average rehearsed seconds per slide index across every rehearsal of the
     * deck — the baseline the live timings are held against.
     *
     * @return array{0: array<int, int>, 1: int}
     */
    private function rehearsedSecondsPerSlide(int $presentationId): array
    {
        $totals = [];
        $counts = [];

        $runs = RehearsalHistoryView::query()
            ->where('presentation_id', $presentationId)
            ->get();

        $runs->each(function (RehearsalHistoryView $run) use (&$totals, &$counts): void {
            foreach ($run->slideTimings() as $timing) {
                $totals[$timing['slide']] = ($totals[$timing['slide']] ?? 0) + $timing['seconds'];
                $counts[$timing['slide']] = ($counts[$timing['slide']] ?? 0) + 1;
            }
        });

        $averages = [];

        foreach ($totals as $slide => $seconds) {
            $averages[$slide] = (int) round($seconds / $counts[$slide]);
        }

        return [$averages, $runs->count()];
    }
}
