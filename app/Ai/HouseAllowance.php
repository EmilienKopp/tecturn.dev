<?php

declare(strict_types=1);

namespace App\Ai;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The free "house" model budget: how many deck builds a user may run per window
 * on the built-in provider before they must bring their own AI. Own-key builds
 * are never counted here. Single source of the limiter key and the numbers, so
 * the enforcing controller and the UI never disagree.
 */
final class HouseAllowance
{
    public static function key(int $userId): string
    {
        return 'deckster-house:'.$userId;
    }

    public static function max(): int
    {
        return (int) config('deckster.house_limit.max');
    }

    public static function remaining(User $user): int
    {
        return max(0, RateLimiter::remaining(self::key($user->id), self::max()));
    }

    public static function exceeded(User $user): bool
    {
        return RateLimiter::tooManyAttempts(self::key($user->id), self::max());
    }

    /**
     * Seconds until the next house build is allowed again.
     */
    public static function availableIn(User $user): int
    {
        return RateLimiter::availableIn(self::key($user->id));
    }

    /**
     * Count one house build against the user's budget.
     */
    public static function record(User $user): void
    {
        RateLimiter::hit(self::key($user->id), (int) config('deckster.house_limit.decay_seconds'));
    }

    /**
     * The shape handed to the frontend for the Magic draft picker.
     *
     * @return array{max: int, remaining: int}
     */
    public static function toArray(User $user): array
    {
        return ['max' => self::max(), 'remaining' => self::remaining($user)];
    }
}
