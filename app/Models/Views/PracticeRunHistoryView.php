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
 * @property array<string, mixed> $content
 * @property array<string, mixed>|null $flow
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PracticeRunHistoryView extends ReadOnlyModel
{
    protected $table = 'practice_run_history';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'slide_timings' => 'array',
            'content' => 'array',
            'flow' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
