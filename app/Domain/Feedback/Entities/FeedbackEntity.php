<?php

declare(strict_types=1);

namespace App\Domain\Feedback\Entities;

use App\Domain\BaseEntity;
use App\Domain\Feedback\Exceptions\InvalidFeedback;
use DateTimeInterface;

class FeedbackEntity extends BaseEntity
{
    private const MAX_LENGTH = 2000;

    private function __construct(
        public string $message,
        public ?int $user_id = null,
        public ?int $id = null,
        public ?DateTimeInterface $created_at = null,
        public ?DateTimeInterface $updated_at = null,
    ) {
        $message = trim($message);

        if ($message === '' || mb_strlen($message) > self::MAX_LENGTH) {
            throw new InvalidFeedback('Feedback must be between 1 and '.self::MAX_LENGTH.' characters.');
        }

        $this->message = $message;
    }

    public static function create(string $message, ?int $userId = null): self
    {
        $self = new self(
            message: $message,
            user_id: $userId,
        );
        $self->onCreated();

        return $self;
    }
}
