<?php

declare(strict_types=1);

namespace App\Domain\Presentation\Entities;

use App\Domain\BaseEntity;
use DateTimeInterface;

/**
 * Child entity of RehearsalReviewEntity — no standalone repository. Comments
 * are created through the review aggregate and persisted with it.
 */
class ReviewCommentEntity extends BaseEntity
{
    public function __construct(
        public int $slide_number,
        public string $message,
        public ?int $rehearsal_review_id = null,
        public ?DateTimeInterface $created_at = null,
        public ?int $id = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'rehearsal_review_id' => $this->rehearsal_review_id,
            'slide_number' => $this->slide_number,
            'message' => $this->message,
            'created_at' => $this->created_at,
        ];
    }
}
