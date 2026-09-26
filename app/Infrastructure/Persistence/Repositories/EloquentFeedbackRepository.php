<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Feedback\Contracts\FeedbackRepository;
use App\Domain\Feedback\Entities\FeedbackEntity;
use App\Models\Feedback;

class EloquentFeedbackRepository implements FeedbackRepository
{
    public function save(FeedbackEntity $feedback): FeedbackEntity
    {
        $model = Feedback::create([
            'user_id' => $feedback->user_id,
            'message' => $feedback->message,
        ]);

        return $model->refresh()->toEntity();
    }
}
