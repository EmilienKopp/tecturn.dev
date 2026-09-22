<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Application\Actions\Presentations\GenerateDeckFromPlan;
use App\Application\Commands\GenerateDeckFromPlanCommand;
use App\Application\Events\DeckGenerated;
use App\Application\Events\DeckGenerationFailed;
use App\Enums\DomainEventType;
use App\Presentation\GeneratingDeckTally;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Runs the slow Deckster AI call off the request, then broadcasts a "ready"
 * event so the frontend can toast the user with a link to their new deck.
 */
class GenerateDeckJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public GenerateDeckFromPlanCommand $command,
        public int $userId,
        public string $teamSlug,
    ) {}

    public function handle(GenerateDeckFromPlan $generateDeck, GeneratingDeckTally $tally): void
    {
        $presentation = $generateDeck->execute($this->command);

        // A short buffer so a fast AI response doesn't produce a jarring, instant toast.
        sleep((int) config('deck.buffer_seconds', 1));

        // Clear the skeleton before broadcasting, so the reload the toast triggers sees the real count.
        $tally->decrement($this->command->team_id);

        foreach ($presentation->getEvents(DomainEventType::CREATED) as $event) {
            event(new DeckGenerated($event, $this->userId, $this->teamSlug));
        }
    }

    public function failed(?Throwable $exception): void
    {
        app(GeneratingDeckTally::class)->decrement($this->command->team_id);

        event(new DeckGenerationFailed($this->userId));
    }
}
