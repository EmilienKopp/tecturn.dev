<?php

declare(strict_types=1);

namespace App\Domain\Presentation\Events;

use Illuminate\Foundation\Events\Dispatchable;

class RehearsalReviewCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly int $rehearsal_review_id,
    ) {}
}
