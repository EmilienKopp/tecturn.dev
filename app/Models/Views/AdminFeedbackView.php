<?php

namespace App\Models\Views;

use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property string $message
 * @property int|null $user_id
 * @property string|null $user_name
 * @property string|null $user_email
 * @property Carbon|null $created_at
 */
class AdminFeedbackView extends ReadOnlyModel
{
    protected $table = 'admin_feedback';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
