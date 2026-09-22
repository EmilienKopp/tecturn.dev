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
 * @property Carbon $ended_at
 * @property int $duration_seconds
 * @property list<array{slide: int, seconds: int}> $slide_timings
 * @property list<array{at_ms: int, slide: int, step: int}>|null $step_events
 * @property array<string, mixed> $content
 * @property array<string, mixed>|null $flow
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property bool $has_recording
 * @property int $pending_review_count
 * @property int $completed_review_count
 */
class RehearsalHistoryView extends ReadOnlyModel
{
    protected $table = 'practice_run_history';

    public $timestamps = false;

    /**
     * Runs saved from multipart posts stored their JSON numbers as strings;
     * these accessors heal that on read so pages can do arithmetic.
     *
     * @return list<array{slide: int, seconds: int}>
     */
    public function slideTimings(): array
    {
        return array_map(
            fn (array $timing): array => [
                'slide' => (int) $timing['slide'],
                'seconds' => (int) $timing['seconds'],
            ],
            $this->slide_timings ?? [],
        );
    }

    /**
     * @return list<array{at_ms: int, slide: int, step: int}>
     */
    public function stepEvents(): array
    {
        return array_map(
            fn (array $event): array => [
                'at_ms' => (int) $event['at_ms'],
                'slide' => (int) $event['slide'],
                'step' => (int) $event['step'],
            ],
            $this->step_events ?? [],
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'slide_timings' => 'array',
            'step_events' => 'array',
            'content' => 'array',
            'flow' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'has_recording' => 'boolean',
            'pending_review_count' => 'integer',
            'completed_review_count' => 'integer',
        ];
    }
}
