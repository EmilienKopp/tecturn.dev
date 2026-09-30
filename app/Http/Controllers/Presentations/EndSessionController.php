<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Application\Actions\Presentations\EndSession;
use App\Application\Commands\EndSessionCommand;
use App\Http\Controllers\Controller;
use App\Models\Presentation;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class EndSessionController extends Controller
{
    public function __construct(private readonly EndSession $endSession) {}

    public function __invoke(Request $request, Team $current_team, Presentation $presentation): Response
    {
        Gate::authorize('view', $presentation);

        $this->endSession->execute(new EndSessionCommand(
            presentationId: $presentation->id,
            endedAt: Carbon::now(),
            slideTimings: $this->slideTimings($request),
            reactionSlides: $this->reactionSlides($request),
        ));

        return response()->noContent();
    }

    /**
     * The per-slide seconds the presenter tracked, or null when absent or
     * malformed. Arrives as a JSON string because navigator.sendBeacon posts
     * FormData, which can't carry nested arrays.
     *
     * @return list<array{slide: int, seconds: int}>|null
     */
    private function slideTimings(Request $request): ?array
    {
        $raw = $request->input('slide_timings');

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return null;
        }

        $timings = [];

        foreach ($decoded as $entry) {
            if (! is_array($entry) || ! is_numeric($entry['slide'] ?? null) || ! is_numeric($entry['seconds'] ?? null)) {
                return null;
            }

            $timings[] = [
                'slide' => (int) $entry['slide'],
                'seconds' => (int) $entry['seconds'],
            ];
        }

        return $timings;
    }

    /**
     * The per-slide reaction tallies from the presenter screen, or null when
     * absent or malformed. Same JSON-string transport as the timings.
     *
     * @return list<array{slide: int, reactions: array<string, int>}>|null
     */
    private function reactionSlides(Request $request): ?array
    {
        $raw = $request->input('reaction_slides');

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded) || $decoded === []) {
            return null;
        }

        $slides = [];

        foreach ($decoded as $entry) {
            if (! is_array($entry) || ! is_numeric($entry['slide'] ?? null) || ! is_array($entry['reactions'] ?? null)) {
                return null;
            }

            $reactions = [];

            foreach ($entry['reactions'] as $emoji => $count) {
                if (! is_string($emoji) || ! is_numeric($count)) {
                    return null;
                }

                $reactions[$emoji] = (int) $count;
            }

            if ($reactions === []) {
                continue;
            }

            $slides[] = [
                'slide' => (int) $entry['slide'],
                'reactions' => $reactions,
            ];
        }

        return $slides === [] ? null : $slides;
    }
}
