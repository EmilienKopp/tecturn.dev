<?php

namespace App\Models\Views;

use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $rehearsal_review_id
 * @property int $slide_number
 * @property string $message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $practice_run_id
 * @property int $reviewer_user_id
 * @property string $reviewer_name
 * @property string|null $reviewer_avatar
 */
class ReviewCommentDetailView extends ReadOnlyModel
{
    protected $table = 'review_comment_details';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // View columns come back untyped (strings on SQLite), and the
            // frontend does arithmetic on slide_number — keep them integers.
            'rehearsal_review_id' => 'integer',
            'slide_number' => 'integer',
            'practice_run_id' => 'integer',
            'reviewer_user_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
