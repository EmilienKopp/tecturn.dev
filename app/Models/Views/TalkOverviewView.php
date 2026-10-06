<?php

namespace App\Models\Views;

use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $team_id
 * @property string $title
 * @property int $deck_count
 * @property int|null $latest_major
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
        ];
    }
}
