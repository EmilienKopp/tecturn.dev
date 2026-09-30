<?php

namespace App\Models\Views;

use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $presentation_id
 * @property int $team_id
 * @property string $presentation_name
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $last_seen_at
 * @property array<string, int> $reaction_counts
 * @property int $reaction_total
 * @property int $viewer_count
 * @property int|null $word_count
 * @property list<array{slide: int, seconds: int}>|null $slide_timings
 * @property list<array{slide: int, reactions: array<string, int>}>|null $reaction_slides
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SessionAnalyticsView extends ReadOnlyModel
{
    protected $table = 'session_analytics';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'reaction_counts' => 'array',
            'slide_timings' => 'array',
            'reaction_slides' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
