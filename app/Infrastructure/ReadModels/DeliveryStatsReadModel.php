<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModels;

use App\Domain\Presentation\ValueObjects\DeliveryStats;
use App\Models\Views\PresentationDeliveryStatsView;
use App\Models\Views\RehearsalHistoryView;
use App\Models\Views\SessionAnalyticsView;

class DeliveryStatsReadModel
{
    /**
     * Measured delivery history for one deck: rehearsal aggregates come from
     * the presentation_delivery_stats view; session lengths, per-slide
     * timings and the words-per-minute pairing need timestamp/JSON
     * arithmetic, which is finished here so the view SQL stays portable.
     */
    public function forPresentation(int $presentationId): DeliveryStats
    {
        return $this->aggregate(['presentation_id' => $presentationId]);
    }

    /**
     * The same numbers across every deck the team rehearsed or presented —
     * the dashboard's "know yourself" read on how the presenter delivers.
     */
    public function forTeam(int $teamId): DeliveryStats
    {
        return $this->aggregate(['team_id' => $teamId]);
    }

    /** @param array<string, int> $where */
    private function aggregate(array $where): DeliveryStats
    {
        $stats = PresentationDeliveryStatsView::query()->where($where)->get();

        $rehearsalCount = (int) $stats->sum('rehearsal_count');
        $sessionCount = (int) $stats->sum('session_count');

        if ($rehearsalCount + $sessionCount === 0) {
            return new DeliveryStats;
        }

        // Words-per-minute only pairs runs that stored a word count (older
        // runs predate the column); words and seconds must come from the
        // same runs or the average would skew.
        $pacedWords = 0;
        $pacedSeconds = 0;

        $sessionSeconds = 0;

        SessionAnalyticsView::query()
            ->where($where)
            ->whereNotNull('ended_at')
            ->get()
            ->each(function (SessionAnalyticsView $session) use (&$sessionSeconds, &$pacedWords, &$pacedSeconds): void {
                $seconds = (int) $session->started_at->diffInSeconds($session->ended_at, true);
                $sessionSeconds += $seconds;

                if ($session->word_count !== null && $seconds > 0) {
                    $pacedWords += $session->word_count;
                    $pacedSeconds += $seconds;
                }
            });

        $timedSlideCount = 0;
        $timedSlideSeconds = 0;

        RehearsalHistoryView::query()
            ->where($where)
            ->get()
            ->each(function (RehearsalHistoryView $run) use (&$timedSlideCount, &$timedSlideSeconds, &$pacedWords, &$pacedSeconds): void {
                foreach ($run->slideTimings() as $timing) {
                    $timedSlideCount++;
                    $timedSlideSeconds += $timing['seconds'];
                }

                if ($run->word_count !== null && $run->duration_seconds > 0) {
                    $pacedWords += $run->word_count;
                    $pacedSeconds += $run->duration_seconds;
                }
            });

        $totalSpokenSeconds = (int) $stats->sum('total_rehearsal_seconds') + $sessionSeconds;
        $runCount = $rehearsalCount + $sessionCount;

        return new DeliveryStats(
            rehearsalCount: $rehearsalCount,
            sessionCount: $sessionCount,
            totalSpokenSeconds: $totalSpokenSeconds,
            avgRunSeconds: (int) round($totalSpokenSeconds / $runCount),
            avgSecondsPerSlide: $timedSlideCount > 0 ? (int) round($timedSlideSeconds / $timedSlideCount) : null,
            avgWordsPerMinute: $pacedSeconds > 0 ? (int) round($pacedWords / ($pacedSeconds / 60)) : null,
        );
    }
}
