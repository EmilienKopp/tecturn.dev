<?php

declare(strict_types=1);

namespace App\Presentation;

use Illuminate\Support\Facades\Cache;

/**
 * Tracks how many decks are currently being generated for a team, so the
 * presentations index can render that many "building…" skeletons. A short TTL
 * keeps a crashed job from leaving a phantom skeleton forever.
 */
class GeneratingDeckTally
{
    private const TTL_SECONDS = 3600;

    public function increment(int $teamId): void
    {
        $key = $this->key($teamId);

        // The database cache store won't increment a missing key, so seed it first.
        Cache::add($key, 0, self::TTL_SECONDS);
        Cache::increment($key);
    }

    public function decrement(int $teamId): void
    {
        $key = $this->key($teamId);

        if ((int) Cache::get($key, 0) > 0) {
            Cache::decrement($key);
        }
    }

    public function count(int $teamId): int
    {
        return max(0, (int) Cache::get($this->key($teamId), 0));
    }

    private function key(int $teamId): string
    {
        return "presentations.generating.{$teamId}";
    }
}
