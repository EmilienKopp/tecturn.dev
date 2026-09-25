<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Application\Actions\Presentations\GenerateDeckFromPlan;
use App\Application\Actions\Presentations\MarkDraftFailed;
use App\Application\Commands\GenerateDeckFromPlanCommand;
use App\Application\Commands\MarkDraftFailedCommand;
use App\Application\Events\DeckGenerated;
use App\Application\Events\DeckGenerationFailed;
use App\Enums\DomainEventType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs the slow Deckster AI call off the request, filling the draft row created
 * up front, then broadcasts a "ready" event so the frontend can toast the user
 * with a link to their new deck. On failure the draft is marked failed so the
 * index can surface the error and offer a retry.
 */
class GenerateDeckJob implements ShouldQueue
{
    use Queueable;

    /** One retry, then give up and mark the draft failed. */
    public int $tries = 2;

    /**
     * Hard cap on a single attempt so a hung provider call can't stall forever.
     * Must stay below the queue connection's retry_after to avoid double runs.
     */
    public int $timeout = 240;

    public function __construct(
        public GenerateDeckFromPlanCommand $command,
        public int $userId,
        public string $teamSlug,
    ) {}

    public function handle(GenerateDeckFromPlan $generateDeck): void
    {
        Log::info('Deck generation started', [
            'presentation_id' => $this->command->presentation_id,
            'user_id' => $this->userId,
        ]);

        $startedAt = microtime(true);

        $presentation = $generateDeck->execute($this->command);

        Log::info('Deck generation completed', [
            'presentation_id' => $presentation->id,
            'user_id' => $this->userId,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);

        // A short buffer so a fast AI response doesn't produce a jarring, instant toast.
        sleep((int) config('deck.buffer_seconds', 1));

        foreach ($presentation->getEvents(DomainEventType::CREATED) as $event) {
            event(new DeckGenerated($event, $this->userId, $this->teamSlug));
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Deck generation failed', [
            'presentation_id' => $this->command->presentation_id,
            'user_id' => $this->userId,
            'exception' => $exception?->getMessage(),
        ]);

        app(MarkDraftFailed::class)->execute(new MarkDraftFailedCommand(
            presentation_id: $this->command->presentation_id,
            error: $exception?->getMessage() ?? 'Unknown error',
        ));

        event(new DeckGenerationFailed($this->userId));
    }
}
