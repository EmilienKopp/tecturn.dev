<?php

declare(strict_types=1);

namespace App\Domain\Feedback\Contracts;

use App\Domain\Feedback\Entities\FeedbackEntity;

interface FeedbackRepository
{
    /**
     * Persist a piece of feedback and return the stored entity.
     */
    public function save(FeedbackEntity $feedback): FeedbackEntity;
}
