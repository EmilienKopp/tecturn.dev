<?php

declare(strict_types=1);

namespace App\Application\Actions\Feedback;

use App\Application\Commands\SubmitFeedbackCommand;
use App\Application\Concerns\EmitsEvents;
use App\Application\Events\FeedbackCreated;
use App\Domain\Feedback\Contracts\FeedbackRepository;
use App\Domain\Feedback\Entities\FeedbackEntity;
use App\Enums\DomainEventType;

class SubmitFeedback
{
    use EmitsEvents;

    public function __construct(
        private readonly FeedbackRepository $feedback,
    ) {}

    /**
     * Record a piece of user feedback and notify the admins.
     */
    public function execute(SubmitFeedbackCommand $command): FeedbackEntity
    {
        $entity = FeedbackEntity::create(
            message: $command->message,
            userId: $command->userId,
        );
        $saved = $this->feedback->save($entity);

        $this->emit(FeedbackCreated::class, $entity, DomainEventType::CREATED);

        return $saved;
    }
}
