<?php

namespace App\Models\Views;

use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property string $name
 * @property string $avatar
 * @property string|null $handle
 * @property string|null $social_x_handle
 * @property string|null $social_github_handle
 * @property int $talks_count
 * @property int $total_viewers
 * @property int $total_reactions
 * @property int $followers_count
 * @property int $following_count
 */
class ContactProfileView extends ReadOnlyModel
{
    protected $table = 'contact_profiles';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'talks_count' => 'integer',
            'total_viewers' => 'integer',
            'total_reactions' => 'integer',
            'followers_count' => 'integer',
            'following_count' => 'integer',
        ];
    }
}
