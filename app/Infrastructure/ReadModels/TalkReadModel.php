<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModels;

use App\Models\Views\TalkOverviewView;

class TalkReadModel
{
    /**
     * The team's talks for pickers: id, title, how many deck versions each
     * one has accumulated and which presentation is the latest finished deck.
     * generated_at is null while the talk's only deck is still an AI draft in
     * progress, so pickers can hide it.
     *
     * @return array<int, array{id: int, title: string, deck_count: int, latest_major: int|null, latest_presentation_id: int|null, generated_at: string|null}>
     */
    public function listForTeam(int $teamId): array
    {
        return TalkOverviewView::query()
            ->where('team_id', $teamId)
            ->orderBy('title')
            ->get()
            ->map(fn (TalkOverviewView $talk): array => [
                'id' => $talk->id,
                'title' => $talk->title,
                'deck_count' => $talk->deck_count,
                'latest_major' => $talk->latest_major,
                'latest_presentation_id' => $talk->latest_presentation_id,
                'generated_at' => $talk->generated_at?->toISOString(),
            ])
            ->all();
    }
}
