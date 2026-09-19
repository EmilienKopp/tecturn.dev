<?php

namespace App\Models\Views;

use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $follower_user_id
 * @property int $followed_user_id
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $follower_name
 * @property string $follower_avatar
 * @property string|null $follower_handle
 * @property string|null $follower_social_x_handle
 * @property string|null $follower_social_github_handle
 * @property string $followed_name
 * @property string $followed_avatar
 * @property string|null $followed_handle
 * @property string|null $followed_social_x_handle
 * @property string|null $followed_social_github_handle
 */
class ContactRelationshipView extends ReadOnlyModel
{
    protected $table = 'contact_relationships';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
