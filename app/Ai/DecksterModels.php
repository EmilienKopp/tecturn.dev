<?php

declare(strict_types=1);

namespace App\Ai;

use Laravel\Ai\Enums\Lab;

/**
 * Read access to the curated Deckster model catalogue in config/deckster.php.
 * The single source of truth for which provider/model pairs are offered in the
 * UI, which drivers may be free-texted, and which require a custom base URL.
 */
final class DecksterModels
{
    /**
     * The curated models offered in the UI. Openai-compatible entries without a
     * configured base URL are hidden, since they cannot be used as-is.
     *
     * @return array<int, array{driver: string, model: string, label: string, supportsSchema: bool, base_url?: string|null}>
     */
    public static function curated(): array
    {
        $models = config('deckster.models', []);

        return array_values(array_filter($models, static function (array $entry): bool {
            if ($entry['driver'] === Lab::OpenAICompatible->value) {
                return ! empty($entry['base_url']);
            }

            return true;
        }));
    }

    /**
     * The house fallback provider/model used when a user has no default credential.
     *
     * @return array{driver: string, model: string}
     */
    public static function house(): array
    {
        return config('deckster.house');
    }

    /**
     * @return list<string>
     */
    public static function freetextDrivers(): array
    {
        return config('deckster.freetext_drivers', []);
    }

    public static function isKnownDriver(string $driver): bool
    {
        return in_array($driver, self::freetextDrivers(), true);
    }

    public static function requiresBaseUrl(string $driver): bool
    {
        return in_array($driver, config('deckster.requires_base_url', []), true);
    }

    /**
     * Whether the given provider/model pair is a curated, schema-verified combo.
     * Free-texted models are treated as unverified (false).
     */
    public static function supportsSchema(string $driver, string $model): bool
    {
        foreach (self::curated() as $entry) {
            if ($entry['driver'] === $driver && $entry['model'] === $model) {
                return $entry['supportsSchema'];
            }
        }

        return false;
    }
}
