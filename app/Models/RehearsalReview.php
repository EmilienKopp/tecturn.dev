<?php

namespace App\Models;

use App\Domain\Presentation\Entities\RehearsalReviewEntity;
use App\Domain\Presentation\Entities\ReviewCommentEntity;
use App\Policies\RehearsalReviewPolicy;
use Database\Factories\RehearsalReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $practice_run_id
 * @property int $requester_user_id
 * @property int $reviewer_user_id
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Rehearsal $rehearsal
 * @property-read Collection<int, ReviewComment> $comments
 */
#[Fillable(['practice_run_id', 'requester_user_id', 'reviewer_user_id', 'status'])]
#[UsePolicy(RehearsalReviewPolicy::class)]
class RehearsalReview extends Model
{
    /** @use HasFactory<RehearsalReviewFactory> */
    use HasFactory;

    protected $table = 'rehearsal_reviews';

    /**
     * @return BelongsTo<Rehearsal, $this>
     */
    public function rehearsal(): BelongsTo
    {
        return $this->belongsTo(Rehearsal::class, 'practice_run_id');
    }

    /**
     * @return HasMany<ReviewComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ReviewComment::class, 'rehearsal_review_id');
    }

    public function toEntity(): RehearsalReviewEntity
    {
        return new RehearsalReviewEntity(
            practice_run_id: $this->practice_run_id,
            requester_user_id: $this->requester_user_id,
            reviewer_user_id: $this->reviewer_user_id,
            status: $this->status,
            comments: array_values(
                $this->comments
                    ->map(fn (ReviewComment $comment): ReviewCommentEntity => $comment->toEntity())
                    ->all()
            ),
            id: $this->id,
        );
    }
}
