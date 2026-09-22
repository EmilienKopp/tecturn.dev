<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags;

use App\Enums\RegistrationMode;

/**
 * The single source of truth for admin-manageable feature flags. Adding a new
 * flag is a one-line entry here plus its Pennant definition in the
 * AppServiceProvider.
 */
class FeatureCatalog
{
    /**
     * @return list<FeatureDefinition>
     */
    public static function all(): array
    {
        return [
            new FeatureDefinition(
                key: 'registration',
                label: 'Registration mode',
                description: 'How new people get into the app.',
                type: 'select',
                scope: 'global',
                options: [
                    ['value' => RegistrationMode::Open->value, 'label' => 'Open'],
                    ['value' => RegistrationMode::Invitation->value, 'label' => 'Invitation'],
                    ['value' => RegistrationMode::Closed->value, 'label' => 'Closed'],
                ],
            ),
            new FeatureDefinition(
                key: 'discovery',
                label: 'People discovery',
                description: 'Lets the contacts directory surface and search other people. Off until per-user discoverability settings exist.',
                type: 'boolean',
                scope: 'global',
            ),
            new FeatureDefinition(
                key: 'live_translation',
                label: 'Live translation',
                description: 'Lets the team start real-time translation sessions while presenting.',
                type: 'boolean',
                scope: 'team',
            ),
        ];
    }

    /**
     * @return list<FeatureDefinition>
     */
    public static function global(): array
    {
        return array_values(array_filter(self::all(), fn (FeatureDefinition $flag): bool => $flag->isGlobal()));
    }

    /**
     * @return list<FeatureDefinition>
     */
    public static function team(): array
    {
        return array_values(array_filter(self::all(), fn (FeatureDefinition $flag): bool => ! $flag->isGlobal()));
    }

    public static function find(string $key): ?FeatureDefinition
    {
        foreach (self::all() as $flag) {
            if ($flag->key === $key) {
                return $flag;
            }
        }

        return null;
    }
}
