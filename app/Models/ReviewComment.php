<?php

namespace App\Models;

use App\Domain\Presentation\Entities\ReviewCommentEntity;
use Database\Factories\ReviewCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $rehearsal_review_id
 * @property int $slide_number
 * @property string $message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read RehearsalReview $review
 */
#[Fillable(['rehearsal_review_id', 'slide_number', 'message'])]
class ReviewComment extends Model
{
    /** @use HasFactory<ReviewCommentFactory> */
    use HasFactory;

    protected $table = 'review_comments';

    /**
     * @return BelongsTo<RehearsalReview, $this>
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(RehearsalReview::class, 'rehearsal_review_id');
    }

    public function toEntity(): ReviewCommentEntity
    {
        return new ReviewCommentEntity(
            slide_number: $this->slide_number,
            message: $this->message,
            rehearsal_review_id: $this->rehearsal_review_id,
            created_at: $this->created_at?->toDateTimeImmutable(),
            id: $this->id,
        );
    }
}
