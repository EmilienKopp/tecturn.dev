<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModels;

use App\Models\Views\TalkOverviewView;

class TalkReadModel
{
    /**
     * The team's talks for pickers: id, title and how many deck versions each
     * one has accumulated.
     *
     * @return array<int, array{id: int, title: string, deck_count: int, latest_major: int|null}>
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
            ])
            ->all();
    }
}
