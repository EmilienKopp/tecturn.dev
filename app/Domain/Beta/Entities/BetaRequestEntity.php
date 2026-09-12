<?php

declare(strict_types=1);

namespace App\Domain\Beta\Entities;

use App\Domain\BaseEntity;
use App\Domain\Beta\Exceptions\InvalidBetaRequest;
use App\Enums\BetaRequestStatus;
use DateTimeInterface;

class BetaRequestEntity extends BaseEntity
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $message = null,
        public BetaRequestStatus $status = BetaRequestStatus::Pending,
        public ?int $id = null,
        public ?DateTimeInterface $created_at = null,
        public ?DateTimeInterface $updated_at = null,
    ) {
        $name = trim($name);
        $email = trim($email);

        if ($name === '' || mb_strlen($name) > 255) {
            throw new InvalidBetaRequest('Name must be between 1 and 255 characters.');
        }

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidBetaRequest('A valid email address is required.');
        }

        $this->name = $name;
        $this->email = $email;
        $this->message = $message !== null && trim($message) !== '' ? trim($message) : null;
    }

    public function approve(): void
    {
        $this->status = BetaRequestStatus::Approved;
    }

    public function reject(): void
    {
        $this->status = BetaRequestStatus::Rejected;
    }
}
