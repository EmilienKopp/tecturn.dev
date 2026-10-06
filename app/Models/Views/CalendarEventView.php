<?php

namespace App\Models\Views;

use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $team_id
 * @property int|null $talk_id
 * @property string $name
 * @property Carbon $date
 * @property string|null $start_time
 * @property string|null $talk_title
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CalendarEventView extends ReadOnlyModel
{
    protected $table = 'calendar_events';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
