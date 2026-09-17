<?php

namespace App\Models\Views;

use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $user_id
 * @property string $user_name
 * @property string $user_avatar
 * @property string|null $user_handle
 * @property string|null $user_social_x_handle
 * @property string|null $user_social_github_handle
 * @property string $name
 * @property bool $is_private
 * @property Carbon|null $updated_at
 * @property Carbon|null $last_presented_at
 * @property int $viewer_count
 * @property int $reaction_total
 */
class ContactTalkView extends ReadOnlyModel
{
    protected $table = 'contact_talks';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
            'updated_at' => 'datetime',
            'last_presented_at' => 'datetime',
            'viewer_count' => 'integer',
            'reaction_total' => 'integer',
        ];
    }
}
