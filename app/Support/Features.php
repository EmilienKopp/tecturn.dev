<?php

namespace App\Support;

use App\Enums\RegistrationMode;
use Laravel\Pennant\Feature;

/**
 * Thin accessor over Pennant for the application's global feature flags.
 *
 * These flags are not scoped to a user, so they always resolve against a
 * single shared scope. The env-backed config value seeds the default; once a
 * flag is toggled at runtime the stored value wins until it is purged.
 */
class Features
{
    /**
     * The shared scope used for global (non-user) feature flags.
     */
    public const GLOBAL_SCOPE = 'global';

    /**
     * Current registration mode for the application.
     */
    public static function registration(): RegistrationMode
    {
        return RegistrationMode::from(
            Feature::for(self::GLOBAL_SCOPE)->value('registration'),
        );
    }
}
