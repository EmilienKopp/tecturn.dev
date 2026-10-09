<?php

namespace App\Models\Views;

use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $team_id
 * @property string $title
 * @property int $deck_count
 * @property int|null $latest_major
 * @property int|null $latest_presentation_id
 * @property Carbon|null $generated_at
 */
class TalkOverviewView extends ReadOnlyModel
{
    protected $table = 'talks_overview';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'deck_count' => 'integer',
            'latest_major' => 'integer',
            'latest_presentation_id' => 'integer',
            'generated_at' => 'datetime',
        ];
    }
}
