<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $practice_run_id
 * @property int $requester_user_id
 * @property int $reviewer_user_id
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $team_id
 * @property int $presentation_id
 * @property Carbon $rehearsed_at
 * @property int $duration_seconds
 * @property string $presentation_name
 * @property string $requester_name
 * @property string|null $requester_avatar
 * @property string|null $requester_handle
 * @property string $reviewer_name
 * @property string|null $reviewer_avatar
 * @property string|null $reviewer_handle
 */
class RehearsalReviewDetailView extends ReadOnlyModel
{
    protected $table = 'rehearsal_review_details';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // View columns come back untyped (strings on SQLite); the pages
            // format duration_seconds and compare ids numerically.
            'practice_run_id' => 'integer',
            'requester_user_id' => 'integer',
            'reviewer_user_id' => 'integer',
            'team_id' => 'integer',
            'presentation_id' => 'integer',
            'duration_seconds' => 'integer',
            'rehearsed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopePendingLast(Builder $query): Builder
    {
        return $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->orderByDesc('created_at');
    }
}
