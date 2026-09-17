<?php

declare(strict_types=1);

namespace App\Domain\Presentation\Entities;

use App\Domain\BaseEntity;
use App\Domain\Presentation\Exceptions\ReviewAlreadyCompleted;

class RehearsalReviewEntity extends BaseEntity
{
    public const string STATUS_PENDING = 'pending';

    public const string STATUS_COMPLETED = 'completed';

    /**
     * A review request on a specific rehearsal run. The reviewer is a user
     * who follows the requester; comments accumulate while the review is
     * pending and freeze once it is completed.
     *
     * @param  list<ReviewCommentEntity>  $comments
     */
    public function __construct(
        public int $practice_run_id,
        public int $requester_user_id,
        public int $reviewer_user_id,
        public string $status = self::STATUS_PENDING,
        public array $comments = [],
        public ?int $id = null,
    ) {}

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function complete(): void
    {
        if ($this->isCompleted()) {
            throw new ReviewAlreadyCompleted('This review has already been completed.');
        }

        $this->status = self::STATUS_COMPLETED;
    }

    public function addComment(int $slideNumber, string $message): ReviewCommentEntity
    {
        if ($this->isCompleted()) {
            throw new ReviewAlreadyCompleted('Comments cannot be added to a completed review.');
        }

        $comment = new ReviewCommentEntity(
            slide_number: $slideNumber,
            message: $message,
            rehearsal_review_id: $this->id,
        );

        $this->comments[] = $comment;

        return $comment;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'practice_run_id' => $this->practice_run_id,
            'requester_user_id' => $this->requester_user_id,
            'reviewer_user_id' => $this->reviewer_user_id,
            'status' => $this->status,
            'comments' => array_map(
                fn (ReviewCommentEntity $comment): array => $comment->toArray(),
                $this->comments,
            ),
        ];
    }
}
