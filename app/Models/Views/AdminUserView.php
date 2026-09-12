<?php

namespace App\Models\Views;

use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $avatar
 * @property Carbon|null $created_at
 * @property int $team_count
 * @property string|null $current_team_name
 */
class AdminUserView extends ReadOnlyModel
{
    protected $table = 'admin_users';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'team_count' => 'integer',
        ];
    }
}
