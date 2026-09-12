<?php

namespace App\Models\Views;

use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $total_users
 * @property int $total_teams
 * @property int $total_workspaces
 * @property int $total_presentations
 * @property int $total_sessions
 * @property int $live_sessions
 * @property int $total_reactions
 * @property int $total_viewers
 */
class AdminOverviewView extends ReadOnlyModel
{
    protected $table = 'admin_overview';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_users' => 'integer',
            'total_teams' => 'integer',
            'total_workspaces' => 'integer',
            'total_presentations' => 'integer',
            'total_sessions' => 'integer',
            'live_sessions' => 'integer',
            'total_reactions' => 'integer',
            'total_viewers' => 'integer',
        ];
    }
}
